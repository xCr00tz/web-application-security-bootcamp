<?php
namespace Concrete\Core\User\PrivateMessage;

use Concrete\Core\Foundation\ConcreteObject;
use Loader;
use UserInfo;
use Events;

class Mailbox extends ConcreteObject
{
    const MBTYPE_INBOX = -1;
    const MBTYPE_SENT = -2;

    public function getMailboxID()
    {
        return $this->msgMailboxID;
    }
    public function getMailboxUserID()
    {
        return $this->uID;
    }

    public static function get($user, $msgMailboxID)
    {
        $db = Loader::db();
        $mb = new self();
        $mb->msgMailboxID = $msgMailboxID;
        $mb->uID = $user->getUserID();
        $mb->totalMessages = $db->GetOne("select count(msgID) from UserPrivateMessagesTo where msgMailboxID = ? and uID = ?", array($msgMailboxID, $user->getUserID()));
        $mb->lastMessageID = $db->GetOne("select UserPrivateMessages.msgID from UserPrivateMessages inner join UserPrivateMessagesTo on UserPrivateMessages.msgID = UserPrivateMessagesTo.msgID where msgMailboxID = ? and UserPrivateMessagesTo.uID = ? order by msgDateCreated desc", array($msgMailboxID, $user->getUserID()));

        return $mb;
    }

    public function removeNewStatus()
    {
        $db = Loader::db();
        $user = UserInfo::getByID($this->uID);

        $ue = new \Concrete\Core\User\Event\UserInfo($user);
        Events::dispatch('on_private_message_marked_not_new', $ue);

        $db->Execute('update UserPrivateMessagesTo set msgIsNew = 0 where msgMailboxID = ? and uID = ?', array($this->msgMailboxID, $user->getUserID()));
    }

    public function removeMessageNewStatus($messageId)
    {
        $db = Loader::db();
        $user = UserInfo::getByID($this->uID);

        $ue = new \Concrete\Core\User\Event\UserInfo($user);
        Events::dispatch('on_private_message_marked_not_new', $ue);

        $db->Execute('update UserPrivateMessagesTo set msgIsNew = 0 where msgMailboxID = ? and uID = ? and msgID = ?', [$this->msgMailboxID, $user->getUserID(), $messageId ]);
    }

    public function getTotalMessages()
    {
        return $this->totalMessages;
    }
    public function getLastMessageID()
    {
        return $this->lastMessageID;
    }
    public function getLastMessageObject()
    {
        if ($this->lastMessageID > 0) {
            return PrivateMessage::getByID($this->lastMessageID, $this);
        }
    }

    public function getMessageList()
    {
        $pml = new PrivateMessageList();
        $pml->filterByMailbox($this);

        return $pml;
    }
}
