<?php

namespace Concrete\Core\Permission;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Http\Request;
use Concrete\Core\Logging\Channels;
use Concrete\Core\Logging\LoggerAwareInterface;
use Concrete\Core\Logging\LoggerAwareTrait;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Utility\IPAddress;
use DateTime;
use IPLib\Address\AddressInterface;
use IPLib\Factory as IPFactory;
use IPLib\Range\RangeInterface;

/**
 * @deprecated check single methods to see the non-deprecated alternatives
 */
class IPService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @deprecated Use \Concrete\Core\Permission\IpAccessControlService::IPRANGEFLAG_BLACKLIST
     *
     * @var int
     */
    const IPRANGEFLAG_BLACKLIST = IpAccessControlService::IPRANGEFLAG_BLACKLIST;

    /**
     * @deprecated Use \Concrete\Core\Permission\IpAccessControlService::IPRANGEFLAG_WHITELIST
     *
     * @var int
     */
    const IPRANGEFLAG_WHITELIST = IpAccessControlService::IPRANGEFLAG_WHITELIST;

    /**
     * @deprecated Use \Concrete\Core\Permission\IpAccessControlService::IPRANGEFLAG_MANUAL
     *
     * @var int
     */
    const IPRANGEFLAG_MANUAL = IpAccessControlService::IPRANGEFLAG_MANUAL;

    /**
     * @deprecated Use \Concrete\Core\Permission\IpAccessControlService::IPRANGEFLAG_AUTOMATIC
     *
     * @var int
     */
    const IPRANGEFLAG_AUTOMATIC = IpAccessControlService::IPRANGEFLAG_AUTOMATIC;

    /**
     * @deprecated Use \Concrete\Core\Permission\IpAccessControlService::IPRANGETYPE_BLACKLIST_MANUAL
     *
     * @var int
     */
    const IPRANGETYPE_BLACKLIST_MANUAL = IpAccessControlService::IPRANGETYPE_BLACKLIST_MANUAL;

    /**
     * @deprecated Use \Concrete\Core\Permission\IpAccessControlService::IPRANGETYPE_BLACKLIST_AUTOMATIC
     *
     * @var int
     */
    const IPRANGETYPE_BLACKLIST_AUTOMATIC = IpAccessControlService::IPRANGETYPE_BLACKLIST_AUTOMATIC;

    /**
     * @deprecated Use \Concrete\Core\Permission\IpAccessControlService::IPRANGETYPE_WHITELIST_MANUAL
     *
     * @var int
     */
    const IPRANGETYPE_WHITELIST_MANUAL = IpAccessControlService::IPRANGETYPE_WHITELIST_MANUAL;

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var \Concrete\Core\Database\Connection\Connection
     */
    protected $connection;

    /**
     * @var \Concrete\Core\Http\Request
     */
    protected $request;

    /**
     * @param \Concrete\Core\Config\Repository\Repository $config
     * @param \Concrete\Core\Database\Connection\Connection $connection
     * @param \Concrete\Core\Http\Request $request
     */
    public function __construct(Repository $config, Connection $connection, Request $request)
    {
        $this->config = $config;
        $this->connection = $connection;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Logging\LoggerAwareInterface::getLoggerChannel()
     */
    public function getLoggerChannel()
    {
        return Channels::CHANNEL_SECURITY;
    }

    /**
     * @deprecated Use $app->make(\IPLib\Address\AddressInterface::class)
     *
     * @return \IPLib\Address\AddressInterface
     */
    public function getRequestIPAddress()
    {
        return IPFactory::addressFromString($this->request->getClientIp());
    }

    /**
     * @deprecated use $app->make('failed_login')->isBlacklisted()
     *
     * @param \IPLib\Address\AddressInterface|null $ip
     *
     * @return bool
     */
    public function isBlacklisted(AddressInterface $ip = null)
    {
        return $this->getFailedLoginService()->isBlacklisted($ip);
    }

    /**
     * @deprecated use $app->make('failed_login')->isWhitelisted()
     *
     * @param \IPLib\Address\AddressInterface|null $ip
     *
     * @return bool
     */
    public function isWhitelisted(AddressInterface $ip = null)
    {
        return $this->getFailedLoginService()->isWhitelisted($ip);
    }

    /**
     * @deprecated use $app->make('failed_login')->getErrorMessage()
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->getFailedLoginService()->getErrorMessage();
    }

    /**
     * @deprecated use $app->make('failed_login')->registerEvent()
     *
     * @param \IPLib\Address\AddressInterface|null $ip
     * @param bool $ignoreConfig
     */
    public function logFailedLogin(AddressInterface $ip = null, $ignoreConfig = false)
    {
        return $this->getFailedLoginService()->registerEvent($ip, $ignoreConfig);
    }

    /**
     * @deprecated use $app->make('failed_login')->isThresholdReached()
     *
     * @param \IPLib\Address\AddressInterface|null $ip
     * @param bool $ignoreConfig
     *
     * @return bool
     */
    public function failedLoginsThresholdReached(AddressInterface $ip = null, $ignoreConfig = false)
    {
        return $this->getFailedLoginService()->isThresholdReached($ip, $ignoreConfig);
    }

    /**
     * @deprecated use $app->make('failed_login')->addToBlacklistForThresholdReached()
     *
     * @param \IPLib\Address\AddressInterface $ip
     * @param bool $ignoreConfig
     */
    public function addToBlacklistForThresholdReached(AddressInterface $ip = null, $ignoreConfig = false)
    {
        $this->getFailedLoginService()->addToBlacklistForThresholdReached($ip, $ignoreConfig);
    }

    /**
     * @deprecated use $app->make('failed_login')->createRange()
     *
     * @param \IPLib\Range\RangeInterface $range
     * @param int $type
     * @param \DateTime|null $expiration
     *
     * @return \Concrete\Core\Permission\IPRange
     */
    public function createRange(RangeInterface $range, $type, DateTime $expiration = null)
    {
        $rangeEntity = $this->getFailedLoginService()->createRange($range, $type, $expiration);

        return IPRange::createFromEntity($rangeEntity);
    }

    /**
     * @deprecated use $app->make('failed_login')->getRanges()
     *
     * @param int $type (one of the IPService::IPRANGETYPE_... constants)
     * @param bool $includeExpired Include expired records?
     *
     * @return \Concrete\Core\Permission\IPRange[]|\Generator
     */
    public function getRanges($type, $includeExpired = false)
    {
        $rangeEntities = $this->getFailedLoginService()->getRanges($type, $includeExpired);
        foreach ($rangeEntities as $rangeEntity) {
            yield IPRange::createFromEntity($rangeEntity);
        }
    }

    /**
     * @deprecated use $app->make('failed_login')->getRangeByID()
     *
     * @param int $id
     *
     * @return \Concrete\Core\Permission\IPRange|null
     */
    public function getRangeByID($id)
    {
        $rangeEntity = $this->getFailedLoginService()->getRangeByID($id);

        return $rangeEntity === null ? null : IPRange::createFromEntity($rangeEntity);
    }

    /**
     * @deprecated use $app->make('failed_login')->deleteRange()
     *
     * @param \Concrete\Core\Permission\IPRange|int $range
     */
    public function deleteRange($range)
    {
        if (!$range) {
            return;
        }
        $id = $range instanceof IPRange ? $range->getID() : $range;
        $this->getFailedLoginService()->deleteRange($id);
    }

    /**
     * @deprecated use $app->make('failed_login')->deleteEvents()
     *
     * @param int|null $maxAge
     *
     * @return int
     */
    public function deleteFailedLoginAttempts($maxAge = null)
    {
        return $this->getFailedLoginService()->deleteEvents($maxAge);
    }

    /**
     * Clear the IP addresses automatically blacklisted.
     *
     * @param bool $onlyExpired Clear only the expired bans?
     *
     * @return int
     */
    public function deleteAutomaticBlacklist($onlyExpired = true)
    {
        return $this->getFailedLoginService()->deleteAutomaticBlacklist($onlyExpired);
    }

    /**
     * @deprecated Use $app->make(\IPLib\Address\AddressInterface::class)
     *
     * @return \Concrete\Core\Utility\IPAddress
     */
    public function getRequestIP()
    {
        $app = Application::getFacadeApplication();
        $ip = $app->make(\IPLib\Address\AddressInterface::class);

        return new IPAddress($ip === null ? null : (string) $ip);
    }

    /**
     * @deprecated use $app->make('failed_login')->isBlacklisted()
     *
     * @param mixed $ip
     */
    public function isBanned($ip = false)
    {
        $ipAddress = null;
        if ($ip instanceof IPAddress) {
            $ipAddress = IPFactory::addressFromString($ip->getIp(IPAddress::FORMAT_IP_STRING));
        }

        return $this->getFailedLoginService()->isBlacklisted($ipAddress);
    }

    /**
     * * @deprecated use $app->make('failed_login')->addToBlacklistForThresholdReached()
     *
     * @param mixed $ip
     * @param mixed $ignoreConfig
     */
    public function createIPBan($ip = false, $ignoreConfig = false)
    {
        $ipAddress = null;
        if ($ip instanceof IPAddress) {
            $ipAddress = IPFactory::addressFromString($ip->getIp(IPAddress::FORMAT_IP_STRING));
        }
        $this->getFailedLoginService()->addToBlacklistForThresholdReached($ipAddress, $ignoreConfig);
    }

    /**
     * @deprecated use $app->make('failed_login')->registerEvent()
     *
     * @param bool $ignoreConfig
     */
    public function logSignupRequest($ignoreConfig = false)
    {
        return $this->getFailedLoginService()->registerEvent(null, $ignoreConfig);
    }

    /**
     * @deprecated use $app->make('failed_login')->isThresholdReached()
     *
     * @param bool $ignoreConfig
     */
    public function signupRequestThreshholdReached($ignoreConfig = false)
    {
        return $this->getFailedLoginService()->isThresholdReached(null, $ignoreConfig);
    }

    /**
     * @deprecated use $app->make('failed_login')->isThresholdReached()
     *
     * @param bool $ignoreConfig
     */
    public function signupRequestThresholdReached($ignoreConfig = false)
    {
        return $this->getFailedLoginService()->isThresholdReached(null, $ignoreConfig);
    }

    /**
     * @deprecated use $app->make('failed_login')->getRangeType()
     *
     * @param \IPLib\Address\AddressInterface $ip
     *
     * @return int|null
     */
    protected function getRangeType(AddressInterface $ip)
    {
        $range = $this->getFailedLoginService()->getRange($ip);

        return $range === null ? null : $range->getType();
    }

    /**
     * @return \Concrete\Core\Permission\IpAccessControlService
     */
    private function getFailedLoginService()
    {
        $app = Application::getFacadeApplication();

        return $app->make('failed_login');
    }
}
