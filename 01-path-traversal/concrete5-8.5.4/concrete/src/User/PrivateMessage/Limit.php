<?php
namespace Concrete\Core\User\PrivateMessage;

use Concrete\Core\Logging\Channels;
use Loader;
use DateTime;
use Config;
use UserInfo;
use Events;
use View;

class Limit
{

    /**
     * @var bool Tracks whether limiting is enabled
     */
    protected static $enabled = true;

    /**
     * checks to see if a user has exceeded their limit for sending private messages.
     *
     * @param int $uID
     *
     * @return bool
     */
    public static function isOverLimit($uID)
    {
        if (Config::get('concrete.user.private_messages.throttle_max') == 0) {
            return false;
        }
        if (Config::get('concrete.user.private_messages.throttle_max_timespan') == 0) {
            return false;
        }
        $db = Loader::db();
        $dt = new DateTime();
        $dt->modify('-'.Config::get('concrete.user.private_messages.throttle_max_timespan').' minutes');
        $v = array($uID, $dt->format('Y-m-d H:i:s'));
        $q = "SELECT COUNT(msgID) as sent_count FROM UserPrivateMessages WHERE uAuthorID = ? AND msgDateCreated >= ?";
        $count = $db->getOne($q, $v);

        if ($count > Config::get('concrete.user.private_messages.throttle_max')) {
            self::notifyAdmin($uID);

            return true;
        } else {
            return false;
        }
    }

    public static function getErrorObject()
    {
        $ve = Loader::helper('validation/error');
        $ve->add(t('You may not send more than %s messages in %s minutes', Config::get('concrete.user.private_messages.throttle_max'), Config::get('concrete.user.private_messages.throttle_max_timespan')));

        return $ve;
    }

    protected function notifyAdmin($offenderID)
    {
        $offender = UserInfo::getByID($offenderID);

        $ue = new \Concrete\Core\User\Event\UserInfo($offender);
        Events::dispatch('on_private_message_over_limit', $ue);

        $admin = UserInfo::getByID(USER_SUPER_ID);

        $app = Facade::getFacadeApplication();
        $logger = $app->make('log/factory')->createLogger(Channels::CHANNEL_SPAM);
        $logger->warning(t("User: %s has tried to send more than %s private messages within %s minutes",
            $offender->getUserName(),
            Config::get('concrete.user.private_messages.throttle_max'),
            Config::get('concrete.user.private_messages.throttle_max_timespan')));

        $mh = Loader::helper('mail');

        $mh->addParameter('offenderUname', $offender->getUserName());
        $mh->addParameter('profileURL', $offender->getUserPublicProfileUrl());
        $mh->addParameter('profilePreferencesURL', View::url('/account/edit_profile'));

        $mh->to($admin->getUserEmail());
        $mh->addParameter('siteName', tc('SiteName', \Core::make('site')->getSite()->getSiteName()));
        $mh->load('private_message_admin_warning');
        $mh->sendMail();
    }

    /**
     * Enable or disable Limits
     *
     * @param bool $enabled
     */
    public static function setEnabled($enabled = true)
    {
        static::$enabled = (bool) $enabled;
    }
}
