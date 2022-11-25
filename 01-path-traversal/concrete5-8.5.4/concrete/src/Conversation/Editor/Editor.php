<?php
namespace Concrete\Core\Conversation\Editor;

use Concrete\Core\Conversation\Conversation;
use Core;
use Database;
use Environment;
use Concrete\Core\Conversation\Message\Message;
use Concrete\Core\Foundation\ConcreteObject;
use Package;
use Concrete\Core\Package\PackageList;

abstract class Editor extends ConcreteObject
{
    /** @var string */
    protected $cnvEditorHandle;
    /** @var string */
    protected $cnvEditorID;
    /** @var int */
    protected $cnvEditorIsActive;
    /** @var string */
    protected $cnvEditorInputName = 'cnvMessageBody';
    /** @var string */
    protected $cnvEditorName;
    /** @var Message */
    protected $cnvMessage;
    /** @var Conversation */
    protected $cnvObject;
    /** @var int */
    protected $pkgID;

    /** @return \Concrete\Core\Asset\AssetPointer[] */
    abstract public function getConversationEditorAssetPointers();

    public function setConversationEditorInputName($input)
    {
        $this->cnvEditorInputName = $input;
    }

    public function getConversationEditorInputName()
    {
        return $this->cnvEditorInputName;
    }

    /**
     * @param Conversation $cnvObject
     */
    public function setConversationObject($cnvObject)
    {
        $this->cnvObject = $cnvObject;
    }

    public function getConversationObject()
    {
        if (!is_object($this->cnvObject)) {
            return $this->cnvMessage->getConversationObject();
        } else {
            return $this->cnvObject;
        }
    }

    /**
     * @param Message $message
     */
    public function setConversationMessageObject(Message $message)
    {
        $this->cnvMessage = $message;
    }

    public function getConversationMessageObject()
    {
        return $this->cnvMessage;
    }

    /**
     * @return string Returns the editor's formatted message
     */
    public function getConversationEditorMessageBody()
    {
        if (!is_object($this->cnvMessage)) {
            return '';
        }
        $text = \Core::make('helper/text');
        $cnv = $this->cnvMessage->getConversationObject();
        return $text->entities($this->cnvMessage->getConversationMessageBody());
    }

    public function getConversationEditorHandle()
    {
        return $this->cnvEditorHandle;
    }

    public function getConversationEditorID()
    {
        return $this->cnvEditorID;
    }

    public function getConversationEditorName()
    {
        return $this->cnvEditorName;
    }

    public function isConversationEditorActive()
    {
        return $this->cnvEditorIsActive;
    }

    public function getPackageID()
    {
        return $this->pkgID;
    }

    /**
     * Looks up and returns the Package.
     *
     * @return string
     */
    public function getPackageHandle()
    {
        return PackageList::getHandle($this->pkgID);
    }

    /**
     * Looks up and returns a Package object for the current Editor's Package ID.
     *
     * @return Package
     */
    public function getPackageObject()
    {
        return Package::getByID($this->pkgID);
    }

    /**
     * @return Editor|null Returns the first found active conversation editor, null if no editor is active
     */
    public static function getActive()
    {
        $db = Database::connection();
        $cnvEditorID = $db->fetchColumn('select cnvEditorID from ConversationEditors where cnvEditorIsActive = 1');
        if ($cnvEditorID) {
            return static::getByID($cnvEditorID);
        }

        return null;
    }

    /**
     * Returns the appropriate conversation editor object for the given cnvEditorID.
     *
     * @param int $cnvEditorID
     *
     * @return Editor|null
     */
    public static function getByID($cnvEditorID)
    {
        $db = Database::connection();
        $r = $db->fetchAssoc(
            'select *
             from ConversationEditors
             where cnvEditorID = ?',
            array($cnvEditorID)
        );

        return static::createFromRecord($r);
    }

    /**
     * Returns the appropriate conversation editor object for the given cnvEditorHandle.
     *
     * @param $cnvEditorHandle
     *
     * @return Editor|null
     */
    public static function getByHandle($cnvEditorHandle)
    {
        $db = Database::connection();
        $r = $db->fetchAssoc(
            'select *
             from ConversationEditors
             where cnvEditorHandle = ?',
            array($cnvEditorHandle)
        );

        return static::createFromRecord($r);
    }

    /**
     * This function is used to instantiate a Conversation Editor object from an associative array.
     *
     * @param array $record an associative array of field value pairs for the ConversationEditor record
     *
     * @return Editor|null
     */
    protected static function createFromRecord($record)
    {
        if (is_array($record) && $record['cnvEditorHandle']) {
            /** @var \Concrete\Core\Utility\Service\Text $textHelper */
            $textHelper = Core::make('helper/text');
            $class = '\\Concrete\\Core\\Conversation\\Editor\\' . $textHelper->camelcase(
                    $record['cnvEditorHandle']
                ) . 'Editor';
            /** @var Editor $sc Really this could be any kind of editor but this should help code completion a bit */
            $sc = Core::make($class);
            $sc->setPropertiesFromArray($record);

            return $sc;
        }

        return null;
    }

    /**
     * outputs an HTML block containing the add message form for the current Conversation Editor.
     */
    public function outputConversationEditorAddMessageForm()
    {
        \View::element(DIRNAME_CONVERSATIONS . '/' . DIRNAME_CONVERSATION_EDITOR . '/' .
            $this->cnvEditorHandle . '/message', ['editor' => $this], $this->getPackageHandle());
    }

    /**
     * Outputs an HTML block containing the message reply form for the current Conversation Editor.
     */
    public function outputConversationEditorReplyMessageForm()
    {
        \View::element(DIRNAME_CONVERSATIONS . '/' . DIRNAME_CONVERSATION_EDITOR . '/' .
            $this->cnvEditorHandle . '/reply', ['editor' => $this], $this->getPackageHandle());
    }

    /**
     * Returns a formatted conversation message body string, based on configuration options supplied.
     *
     * @param \Concrete\Core\Conversation\Conversation $cnv
     * @param string $cnvMessageBody
     * @param array $config
     *
     * @return string
     */
    public function formatConversationMessageBody($cnv, $cnvMessageBody, $config = array())
    {
        /** @var  \Concrete\Core\Html\Service\Html $htmlHelper */
        $htmlHelper = Core::make('helper/html');
        $cnvMessageBody = $htmlHelper->noFollowHref($cnvMessageBody);
        if (isset($config['htmlawed'])) {
            $default = array('safe' => 1, 'elements' => 'p, br, strong, em, strike, a');
            $conf = array_merge($default, (array) $config['htmlawed']);
            $result = htmLawed($cnvMessageBody, $conf);
        } else {
            $result = $cnvMessageBody;
        }
        if (isset($config['mention']) && $config['mention'] !== false) {
            $users = $cnv->getConversationMessageUsers();
            $needle = array();
            $haystack = array();
            foreach ($users as $user) {
                $needle[] = "@" . $user->getUserName();
                $haystack[] = "<a href='" . $user->getUserPublicProfileURL() . "'>'@" . $user->getUserName() . "</a>";
            }

            $result = str_ireplace($needle, $haystack, $result);
        }

        // Replace any potential XSS
        $result = $this->removeJavascriptLinks($result);
        return $result;
    }

    /**
     * Replace javascript links with dummy links
     *
     * @param string $html
     * @return string 
     */
    protected function removeJavascriptLinks($html)
    {
        // Use regex to replace javascript links with javascript:void
        $html = preg_replace('/\bhref\s*=\s*["\']?\s*(javascript|data)\s*:/i', 'data-blocked-$0/', $html);

        return $html;
    }

    /**
     * Creates a database record for the Conversation Editor, then attempts to return the object.
     *
     * @param string $cnvEditorHandle
     * @param string $cnvEditorName
     * @param bool|Package $pkg
     *
     * @return Editor|null
     */
    public static function add($cnvEditorHandle, $cnvEditorName, $pkg = false)
    {
        $pkgID = 0;
        if (is_object($pkg)) {
            $pkgID = $pkg->getPackageID();
        }
        $db = Database::connection();
        $db->insert(
            'ConversationEditors',
            array(
                'cnvEditorHandle' => $cnvEditorHandle,
                'cnvEditorName' => $cnvEditorName,
                'pkgID' => $pkgID,
            )
        );

        return static::getByHandle($cnvEditorHandle);
    }

    /**
     * Removes the current editor object's record from the database.
     */
    public function delete()
    {
        $db = Database::connection();
        $db->delete('ConversationEditors', array('cnvEditorID' => $this->cnvEditorID));
    }

    /**
     * Deactivates all other Conversation Editors, and activates the current one.
     */
    public function activate()
    {
        $db = Database::connection();
        static::deactivateAll();
        $db->update('ConversationEditors', array('cnvEditorIsActive' => 1), array('cnvEditorID' => $this->cnvEditorID));
    }

    /**
     * Function used to deactivate.
     */
    protected function deactivateAll()
    {
        $db = Database::connection();
        $db->update('ConversationEditors', array('cnvEditorIsActive' => 0), array('cnvEditorIsActive' => 1));
    }

    /**
     * Returns an array of all Editor Objects.
     *
     * @param null $pkgID An optional filter for Package ID
     *
     * @return Editor[]
     */
    public static function getList($pkgID = null)
    {
        $db = Database::connection();
        $queryBuilder = $db->createQueryBuilder()
            ->select('e.*')
            ->from('ConversationEditors', 'e')
            ->orderBy('cnvEditorHandle', 'asc');
        if ($pkgID !== null) {
            $queryBuilder->andWhere('e.pkgID = :pkgID')->setParameter('pkgID', $pkgID);
        }

        $cnvEditors = $db->fetchAll($queryBuilder->getSQL(), $queryBuilder->getParameters());
        $editors = array();
        foreach ($cnvEditors as $editorRecord) {
            $cnvEditor = static::createFromRecord($editorRecord);
            $editors[] = $cnvEditor;
        }

        return $editors;
    }

    /**
     * Returns an array of all Editor objects for the given package object.
     *
     * @param Package $pkg
     *
     * @return Editor[]
     */
    public static function getListByPackage($pkg)
    {
        return static::getList($pkg->getPackageID());
    }

    public function export($xml)
    {
        $type = $xml->addChild('editor');
        $type->addAttribute('handle', $this->getConversationEditorHandle());
        $type->addAttribute('name', $this->getConversationEditorName());
        $type->addAttribute('package', $this->getPackageHandle());
        $type->addAttribute('activated', $this->isConversationEditorActive());
    }

    /**
     * Adds a ConversationEditors node and all Editor records to the provided SimleXMLElement object provided.
     *
     * @param \SimpleXMLElement $xml
     */
    public static function exportList($xml)
    {
        $list = static::getList();
        $nxml = $xml->addChild('conversationeditors');

        foreach ($list as $sc) {
            $sc->export($nxml);
        }
    }

    /**
     * Returns whether or not the current Conversation Editor has an options form.
     *
     * @return bool
     */
    public function hasOptionsForm()
    {
        $env = Environment::get();
        $rec = $env->getRecord(
            DIRNAME_ELEMENTS . '/' . DIRNAME_CONVERSATIONS . '/' . DIRNAME_CONVERSATION_EDITOR . '/' .
            $this->cnvEditorHandle . '/' . FILENAME_CONVERSATION_EDITOR_OPTIONS,
            $this->getPackageHandle()
        );

        return $rec->exists();
    }
}
