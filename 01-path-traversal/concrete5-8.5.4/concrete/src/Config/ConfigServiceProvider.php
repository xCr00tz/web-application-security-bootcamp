<?php
namespace Concrete\Core\Config;

use Concrete\Core\Foundation\Service\Provider;

class ConfigServiceProvider extends Provider
{
    /**
     * Configuration repositories.
     */
    public function register()
    {
        $this->registerFileConfig();
        $this->registerDatabaseConfig();

        // Bind the concrete types
        $this->app->bind('Concrete\Core\Config\Repository\Repository', 'config');
        $this->app->bind('Illuminate\Config\Repository', 'Concrete\Core\Config\Repository\Repository');
    }

    /**
     * Create a file config repository.
     */
    private function registerFileConfig()
    {
        $this->app->bindIf(LoaderInterface::class, static function($app) {
            return $app->make(CompositeLoader::class, [$app, [
                CoreFileLoader::class,
                FileLoader::class,
            ]]);
        });
        $this->app->bindIf(SaverInterface::class, FileSaver::class);

        $this->app->singleton('config', function ($app) {
            $loader = $app->make(LoaderInterface::class);
            $saver = $app->make(SaverInterface::class);

            return $app->build('Concrete\Core\Config\Repository\Repository', array($loader, $saver, $app->environment()));
        });
    }

    /**
     * Create a database config repository.
     */
    private function registerDatabaseConfig()
    {
        $this->app->bindShared('config/database', function ($app) {
            $loader = $app->make('Concrete\Core\Config\DatabaseLoader');
            $saver = $app->make('Concrete\Core\Config\DatabaseSaver');

            return $app->build('Concrete\Core\Config\Repository\Repository', array($loader, $saver, $app->environment()));
        });
    }
}
