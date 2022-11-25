<?php
namespace Concrete\Core\Session;

use Concrete\Core\Application\Application;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Http\Request;
use Concrete\Core\Session\Storage\Handler\NativeFileSessionHandler;
use Illuminate\Support\Str;
use Memcached;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Concrete\Core\Session\Storage\Handler\RedisSessionHandler;
use Redis;
use RedisArray;

/**
 * Class SessionFactory
 * Base concrete5 session factory.
 *
 * To add custom handlers, extend this class and for a handler named "custom_test"
 * create a protected method `getCustomTestHandler`
 */
class SessionFactory implements SessionFactoryInterface
{
    protected $app;

    /**
     * The request object
     * We needed a reference to this object so that we could assign the session object to it.
     * Instead we are using the $app container to resolve the request at the time the session is created.
     * This makes testing a little harder, but ensures we apply the session object to the most accurate request instance.
     * Ideally neither would be required, as the operation creating the session would manage associating the two.
     *
     * @var \Concrete\Core\Session\Request
     *
     * @deprecated
     */
    protected $request;

    /**
     * SessionFactory constructor.
     *
     * @param \Concrete\Core\Application\Application $app
     * @param \Concrete\Core\Http\Request $request @deprecated, will be removed
     */
    public function __construct(Application $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
    }

    /**
     * Create a new symfony session object
     * This method MUST NOT start the session.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function createSession()
    {
        $config = $this->app['config']['concrete.session'];
        $storage = $this->getSessionStorage($config);

        // We have to use "build" here because we have bound this classname to this factory method
        $session = $this->app->build(SymfonySession::class, [$storage]);
        $session->setName(array_get($config, 'name'));

        /* @TODO Remove this call. We should be able to set this against the request somewhere much higher than this */
        /*       At the very least we should have an observer that can track the session status and set this */
        $this->app->make(\Concrete\Core\Http\Request::class)->setSession($session);

        return $session;
    }

    /**
     * Create and return a newly built file session handler.
     *
     * @param array $config The `concrete.session` config item
     *
     * @return \Concrete\Core\Session\Storage\Handler\NativeFileSessionHandler
     */
    protected function getFileHandler(array $config)
    {
        return $this->app->make(NativeFileSessionHandler::class, [
            array_get($config, 'save_path'),
        ]);
    }

    /**
     * Create a new database session handler to handle session.
     *
     * @param array $config The `concrete.session` config item
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
     */
    protected function getDatabaseHandler(array $config)
    {
        return $this->app->make(PdoSessionHandler::class, [
            $this->app->make(Connection::class)->getWrappedConnection(),
            [
                'db_table' => 'Sessions',
                'db_id_col' => 'sessionID',
                'db_data_col' => 'sessionValue',
                'db_time_col' => 'sessionTime',
                'db_lifetime_col' => 'sessionLifeTime',
                'lock_mode' => PdoSessionHandler::LOCK_ADVISORY,
            ],
        ]);
    }

    /**
     * Return a built Memcached session handler.
     *
     * @param array $config The `concrete.session` config item
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler
     */
    protected function getMemcachedHandler(array $config)
    {
        // Create new memcached instance
        $memcached = $this->app->make(Memcached::class, [
            'CCM_SESSION',
            null,
        ]);

        $servers = array_get($config, 'servers', []);

        // Add missing servers
        foreach ($this->newMemcachedServers($memcached, $servers) as $server) {
            $memcached->addServer(
                array_get($server, 'host'),
                array_get($server, 'port'),
                array_get($server, 'weight')
            );
        }

        // Return a newly built handler
        return $this->app->make(MemcachedSessionHandler::class, [
            $memcached,
            ['prefix' => array_get($config, 'name') ?: 'CCM_SESSION'],
        ]);
    }

    /**
     * Return the default session handler.
     *
     * @param array $config The `concrete.session` config item
     *
     * @return \Concrete\Core\Session\Storage\Handler\NativeFileSessionHandler
     */
    protected function getDefaultHandler(array $config)
    {
        return $this->getFileHandler($config);
    }

    /**
     * Get a session storage object based on configuration.
     *
     * @param array $config
     *
     * @return \Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface
     */
    private function getSessionStorage(array $config)
    {
        $app = $this->app;

        // If we're running through command line, just early return an in-memory storage
        if ($app->isRunThroughCommandLineInterface()) {
            return $app->make(MockArraySessionStorage::class);
        }

        // Resolve the handler based on config
        $handler = $this->getSessionHandler($config);
        $storage = $app->make(NativeSessionStorage::class, [[], $handler]);

        // Initialize the storage with some options
        $options = array_get($config, 'cookie', []) + [
            'gc_maxlifetime' => (int) array_get($config, 'max_lifetime') ?: (int) ini_get('session.gc_maxlifetime') ?: 7200,
            'gc_probability' => (int) array_get($config, 'gc_probability') ?: (int) ini_get('session.gc_probability') ?: 1,
            'gc_divisor' => (int) array_get($config, 'gc_divisor') ?: (int) ini_get('session.gc_divisor') ?: 100,
        ];

        if (array_get($options, 'cookie_path', false) === false) {
            $options['cookie_path'] = $app['app_relative_path'] . '/';
        }

        $storage->setOptions($options);

        return $app->make(Storage\LoggedStorage::class, [$storage]);
    }

    /**
     * Get a new session handler.
     *
     * @param array $config The config from our config repository
     *
     * @return \SessionHandlerInterface
     *
     * @throws \RuntimeException When a configured handler does not exist
     */
    private function getSessionHandler(array $config)
    {
        $handler = array_get($config, 'handler', 'default');

        // Build handler using a matching method "get{Type}Handler"
        $method = Str::camel("get_{$handler}_handler");
        if (method_exists($this, $method)) {
            return $this->{$method}($config);
        }

        /*
         * @todo Change this to return an exception if an unsupported handler is configured. This makes it easier to get
         * configuration dialed in properly
         */
        //throw new \RuntimeException(t('Unsupported session handler "%s"', $handler));

        // Return the default session handler by default
        return $this->getSessionHandler(['handler' => 'default'] + $config);
    }

    /**
     * Generator for only returning hosts that aren't already added to the memcache instance.
     *
     * @param \Memcached $memcached
     * @param array $servers The servers as described in config
     *
     * @return \Generator|string[] [ $host, $port, $weight ]
     */
    private function newMemcachedServers(Memcached $memcached, array $servers)
    {
        $serverIndex = [];
        $existingServers = $memcached->getServerList();

        foreach ($existingServers as $server) {
            $serverIndex[$server['host'] . ':' . $server['port']] = true;
        }

        foreach ($servers as $configServer) {
            $server = [
                'host' => array_get($configServer, 'host', ''),
                'port' => array_get($configServer, 'port', 11211),
                'weight' => array_get($configServer, 'weight', 0),
            ];

            if (!isset($serverIndex[$server['host'] . ':' . $server['port']])) {
                yield $server;
            }
        }
    }

    /**
     * Return a built Redis session handler.
     *
     * @param array $config The `concrete.session` config item
     *
     * @return \Concrete\Core\Session\Storage\Handler\RedisSessionHandler
     */
    protected function getRedisHandler(array $config)
    {
        $options = array_get($config, 'redis', []);
        // In case anyone puts the servers under redis configuration - similar to how we handle cache
        $servers = array_get($options, 'servers', []);
        if (empty($servers)) {
            $servers = array_get($config, 'servers', []);
        }

        $redis = $this->getRedisInstance($servers);
        if (!empty($options['database'])) {
            $redis->select((int) $options['database']);
        }

        // In case of anyone setting prefix on the redis server directly
        // Similar to how we do it on cache
        $prefix = array_get($options, 'prefix', 'CCM_SESSION');

        // We pass the prefix to the Redis Handler when we build it
        return $this->app->make(RedisSessionHandler::class, [$redis, ['prefix' => array_get($config, 'name') ?: $prefix]]);
    }

    /**
     *  Decides whether to return a Redis Instance or RedisArray Instance depending on the number of servers passed to it.
     *
     * @param array $servers The `concrete.session.servers` or `concrete.session.redis.servers` config item
     *
     * @return \Redis | \RedisArray
     */
    private function getRedisInstance(array $servers)
    {
        if (count($servers) == 1) {
            // If we only have one server in our array then we just reconnect to it
            $server = $servers[0];
            $redis = $this->app->make(Redis::class);

            if (isset($server['socket']) && $server['socket']) {
                $redis->connect($server['socket']);
            } else {
                $host = array_get($server, 'host', '');
                $port = array_get($server, 'port', 6379);
                $ttl = array_get($server, 'ttl', 0.5);
                // Check for both server/host - fallback due to cache using server
                $host = !empty($host) ? $host : array_get($server, 'server', '127.0.0.1');

                $redis->connect($host, $port, $ttl);
            }

            // Authorisation is handled by just a password
            if (isset($server['password'])) {
                $redis->auth($server['password']);
            }
        } else {
            $serverArray = [];
            $ttl = 0.5;
            $password = null;
            foreach ($this->getRedisServers($servers) as $server) {
                $serverString = $server['server'];
                if (isset($server['port'])) {
                    $serverString .= ':' . $server['port'];
                }
                // We can only use one ttl for connection timeout so use the last set ttl
                // isset allows for 0 - unlimited
                if (isset($server['ttl'])) {
                    $ttl = $server['ttl'];
                }
                if (isset($server['password'])) {
                    $password = $server['password'];
                }

                $serverArray[] = $serverString;
            }
            $options = ['connect_timeout' => $ttl];
            if ($password !== null) {
                $options['auth'] = $password;
            }
            $redis = $this->app->make(RedisArray::class, [$serverArray, $options]);
        }

        return $redis;
    }

    /**
     * Generator for Redis Array.
     *
     * @param array $servers The `concrete.session.servers` or `concrete.session.redis.servers` config item
     *
     * @return \Generator| string[] [ $server, $port, $ttl ]
     */
    private function getRedisServers(array $servers)
    {
        if (!empty($servers)) {
            foreach ($servers as $server) {
                if (isset($server['socket'])) {
                    $server = [
                        'server' => array_get($server, 'socket', ''),
                        'ttl' => array_get($server, 'ttl', null),
                        'password' => array_get($server, 'password', null),
                    ];
                } else {
                    $host = array_get($server, 'host', '');
                    // Check for both server/host - fallback due to cache using server
                    $host = !empty($host) ?: array_get($server, 'server', '127.0.0.1');
                    $server = [
                        'server' => $host,
                        'port' => array_get($server, 'port', 11211),
                        'ttl' => array_get($server, 'ttl', null),
                        'password' => array_get($server, 'password', null),
                    ];
                }
                yield $server;
            }
        } else {
            yield ['server' => '127.0.0.1', 'port' => '6379', 'ttl' => 0.5];
        }
    }
}
