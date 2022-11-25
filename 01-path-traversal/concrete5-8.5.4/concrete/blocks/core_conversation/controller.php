<?php
namespace Concrete\Block\CoreConversation;

use Concrete\Core\Attribute\Category\PageCategory;
use Concrete\Core\Block\Block;
use Concrete\Core\Entity\Attribute\Key\PageKey;
use Concrete\Core\Block\BlockController;
use Concrete\Core\Conversation\Conversation;
use Concrete\Core\Conversation\Message\MessageList;
use Concrete\Core\Feature\ConversationFeatureInterface;
use Concrete\Core\User\User;
use Page;

/**
 * The controller for the conversation block. This block is used to display conversations in a page.
 *
 * @package Blocks
 * @subpackage Conversation
 *
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2013 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */
class Controller extends BlockController implements ConversationFeatureInterface
{
    protected $btInterfaceWidth = 450;
    protected $btInterfaceHeight = 400;
    protected $btCacheBlockRecord = true;
    protected $btTable = 'btCoreConversation';
    protected $conversation;
    protected $btWrapperClass = 'ccm-ui';
    protected $btCopyWhenPropagate = true;
    protected $btFeatures = [
        'conversation',
    ];

    public $enableTopCommentReviews;
    public $reviewAggregateAttributeKey;

    public function getBlockTypeDescription()
    {
        return t("Displays conversations on a page.");
    }

    public function getBlockTypeName()
    {
        return t("Conversation");
    }

    public function getSearchableContent()
    {
        $ml = new MessageList();
        $ml->filterByConversation($this->getConversationObject());
        $messages = $ml->get();
        if (!count($messages)) {
            return '';
        }

        $content = '';
        foreach ($messages as $message) {
            $content .= $message->getConversationMessageSubject() . ' ' .
                       strip_tags($message->getConversationMessageBody()) . ' ';
        }

        return rtrim($content);
    }

    public function getConversationFeatureDetailConversationObject()
    {
        return $this->getConversationObject();
    }

    public function getConversationObject()
    {
        if (!isset($this->conversation)) {
            // i don't know why this->cnvid isn't sticky in some cases, leading us to query
            // every damn time
            $db = $this->app->make('database');
            $cnvID = $db->fetchColumn('select cnvID from btCoreConversation where bID = ?', [$this->bID]);
            $this->conversation = Conversation::getByID($cnvID);
        }

        return $this->conversation;
    }

    public function duplicate_master($newBID, $newPage)
    {
        parent::duplicate($newBID);
        $db = $this->app->make('database');
        $conv = Conversation::add();
        $conv->setConversationPageObject($newPage);
        $this->conversation = $conv;
        $db->executeQuery('update btCoreConversation set cnvID = ? where bID = ?', [$conv->getConversationID(), $newBID]);
    }

    public function edit()
    {
        $keys = $this->getReviewAttributeKeys();
        $this->set('reviewAttributeKeys', iterator_to_array($keys));

        $fileSettings = $this->getFileSettings();
        $this->set('maxFilesGuest', $fileSettings['maxFilesGuest']);
        $this->set('maxFilesRegistered', $fileSettings['maxFilesRegistered']);
        $this->set('maxFileSizeGuest', $fileSettings['maxFileSizeGuest']);
        $this->set('maxFileSizeRegistered', $fileSettings['maxFileSizeRegistered']);
        $this->set('fileExtensions', $fileSettings['fileExtensions']);
        $this->set('attachmentsEnabled', $fileSettings['attachmentsEnabled'] > 0 ? $fileSettings['attachmentsEnabled'] : '');
        $this->set('attachmentOverridesEnabled', $fileSettings['attachmentOverridesEnabled'] > 0 ? $fileSettings['attachmentOverridesEnabled'] : '');

        $conversation = $this->getConversationObject();
        $this->set('notificationOverridesEnabled', $conversation->getConversationNotificationOverridesEnabled());
        $this->set('subscriptionEnabled', $conversation->getConversationSubscriptionEnabled());
        $this->set('notificationUsers', $conversation->getConversationSubscribedUsers());
    }

    public function registerViewAssets($outputContent = '')
    {
        $this->requireAsset('core/conversation');
        $this->requireAsset('core/lightbox');
        $u = $this->app->make(User::class);
        if (!$u->isRegistered()) {
            $this->requireAsset('css', 'core/frontend/captcha');
        }
    }

    public function view()
    {
        if ($this->enableTopCommentReviews) {
            $this->requireAsset('javascript', 'jquery/awesome-rating');
            $this->requireAsset('css', 'jquery/awesome-rating');
        }
        $fileSettings = $this->getFileSettings();
        $conversation = $this->getConversationObject();
        if (is_object($conversation)) {
            $tokenHelper = $this->app->make('token');
            $this->set('conversation', $conversation);
            if ($this->enablePosting) {
                $addMessageToken = $tokenHelper->generate('add_conversation_message');
            } else {
                $addMessageToken = '';
            }
            $this->set('addMessageToken', $addMessageToken);
            $this->set('editMessageToken', $tokenHelper->generate('edit_conversation_message'));
            $this->set('deleteMessageToken', $tokenHelper->generate('delete_conversation_message'));
            $this->set('flagMessageToken', $tokenHelper->generate('flag_conversation_message'));
            $this->set('cID', Page::getCurrentPage()->getCollectionID());
            $this->set('users', $this->getActiveUsers(true));
            $this->set('maxFilesGuest', $fileSettings['maxFilesGuest']);
            $this->set('maxFilesRegistered', $fileSettings['maxFilesRegistered']);
            $this->set('maxFileSizeGuest', $fileSettings['maxFileSizeGuest']);
            $this->set('maxFileSizeRegistered', $fileSettings['maxFileSizeRegistered']);
            $this->set('fileExtensions', $fileSettings['fileExtensions']);
            $this->set('attachmentsEnabled', $fileSettings['attachmentsEnabled']);
            $this->set('attachmentOverridesEnabled', $fileSettings['attachmentOverridesEnabled']);
        }
    }

    public function getFileSettings()
    {
        $conversation = $this->getConversationObject();
        $helperFile = $this->app->make('helper/concrete/file');
        $maxFilesGuest = $conversation->getConversationMaxFilesGuest();
        $attachmentOverridesEnabled = $conversation->getConversationAttachmentOverridesEnabled();
        $maxFilesRegistered = $conversation->getConversationMaxFilesRegistered();
        $maxFileSizeGuest = $conversation->getConversationMaxFileSizeGuest();
        $maxFileSizeRegistered = $conversation->getConversationMaxFileSizeRegistered();
        $fileExtensions = $conversation->getConversationFileExtensions();
        $attachmentsEnabled = $conversation->getConversationAttachmentsEnabled();

        $fileExtensions = implode(',', $helperFile->unserializeUploadFileExtensions($fileExtensions)); //unserialize and implode extensions into comma separated string

        $fileSettings = [];
        $fileSettings['maxFileSizeRegistered'] = $maxFileSizeRegistered;
        $fileSettings['maxFileSizeGuest'] = $maxFileSizeGuest;
        $fileSettings['maxFilesGuest'] = $maxFilesGuest;
        $fileSettings['maxFilesRegistered'] = $maxFilesRegistered;
        $fileSettings['fileExtensions'] = $fileExtensions;
        $fileSettings['attachmentsEnabled'] = $attachmentsEnabled;
        $fileSettings['attachmentOverridesEnabled'] = $attachmentOverridesEnabled;

        return $fileSettings;
    }

    public function getActiveUsers($lower = false)
    {
        $cnv = $this->getConversationObject();
        $uobs = $cnv->getConversationMessageUsers();
        $users = [];
        foreach ($uobs as $user) {
            if ($lower) {
                $users[] = strtolower($user->getUserName());
            } else {
                $users[] = $user->getUserName();
            }
        }

        return $users;
    }

    public function save($post)
    {
        $helperFile = $this->app->make('helper/concrete/file');
        $db = $this->app->make('database');
        $cnvID = $db->fetchColumn('select cnvID from btCoreConversation where bID = ?', [$this->bID]);
        if (!$cnvID) {
            $conversation = Conversation::add();
            $b = $this->getBlockObject();
            $xc = $b->getBlockCollectionObject();
            $conversation->setConversationPageObject($xc);
        } else {
            $conversation = Conversation::getByID($cnvID);
        }
        $values = $post + [
            'attachmentOverridesEnabled' => null,
            'attachmentsEnabled' => null,
            'itemsPerPage' => null,
            'maxFilesGuest' => null,
            'maxFilesRegistered' => null,
            'maxFileSizeGuest' => null,
            'maxFileSizeRegistered' => null,
            'enableOrdering' => null,
            'enableCommentRating' => null,
            'displaySocialLinks' => null,
            'enableTopCommentReviews' => null,
            'notificationOverridesEnabled' => null,
            'subscriptionEnabled' => null,
            'fileExtensions' => null,
        ];
        if ($values['attachmentOverridesEnabled']) {
            $conversation->setConversationAttachmentOverridesEnabled(intval($values['attachmentOverridesEnabled']));
            if ($values['attachmentsEnabled']) {
                $conversation->setConversationAttachmentsEnabled(1);
            } else {
                $conversation->setConversationAttachmentsEnabled(0);
            }
        } else {
            $conversation->setConversationAttachmentOverridesEnabled(0);
        }
        if (!$values['itemsPerPage']) {
            $values['itemsPerPage'] = 0;
        }
        if ($values['maxFilesGuest']) {
            $conversation->setConversationMaxFilesGuest(intval($values['maxFilesGuest']));
        }
        if ($values['maxFilesRegistered']) {
            $conversation->setConversationMaxFilesRegistered(intval($values['maxFilesRegistered']));
        }
        if ($values['maxFileSizeGuest']) {
            $conversation->setConversationMaxFileSizeGuest(intval($values['maxFileSizeGuest']));
        }
        if ($values['maxFileSizeRegistered']) {
            $conversation->setConversationMaxFilesRegistered(intval($values['maxFileSizeRegistered']));
        }
        if (!$values['enableOrdering']) {
            $values['enableOrdering'] = 0;
        }
        if (!$values['enableCommentRating']) {
            $values['enableCommentRating'] = 0;
        }
        if (!$values['enableTopCommentReviews']) {
            $values['enableTopCommentReviews'] = 0;
        }
        if (!$values['displaySocialLinks']) {
            $values['displaySocialLinks'] = 0;
        }

        if ($values['notificationOverridesEnabled']) {
            $conversation->setConversationNotificationOverridesEnabled(true);
            $users = [];
            if (is_array($this->post('notificationUsers'))) {
                foreach ($this->post('notificationUsers') as $uID) {
                    $ui = \UserInfo::getByID($uID);
                    if (is_object($ui)) {
                        $users[] = $ui;
                    }
                }
            }
            $conversation->setConversationSubscribedUsers($users);
            $conversation->setConversationSubscriptionEnabled(intval($values['subscriptionEnabled']));
        } else {
            $conversation->setConversationNotificationOverridesEnabled(false);
            $conversation->setConversationSubscriptionEnabled(0);
        }

        if ($values['fileExtensions']) {
            $receivedExtensions = preg_split('{,}', strtolower($values['fileExtensions']), null, PREG_SPLIT_NO_EMPTY);
            $fileExtensions = $helperFile->serializeUploadFileExtensions($receivedExtensions);
            $conversation->setConversationFileExtensions($fileExtensions);
        }

        $values['cnvID'] = $conversation->getConversationID();
        parent::save($values);
    }

    /**
     * @return \Generator
     */
    private function getReviewAttributeKeys()
    {
        $category = $this->app->make(PageCategory::class);
        $keys = $category->getAttributeKeyRepository()->findAll();

        /** @var PageKey $key */
        foreach ($keys as $key) {
            if ($key->getAttributeType()->getAttributeTypeHandle() == 'rating') {
                yield $key->getAttributeKeyID() => $key->getAttributeKeyDisplayName();
            }
        }
    }
}
