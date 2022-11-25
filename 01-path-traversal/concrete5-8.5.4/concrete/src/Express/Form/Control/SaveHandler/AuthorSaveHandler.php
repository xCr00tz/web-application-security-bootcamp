<?php
namespace Concrete\Core\Express\Form\Control\SaveHandler;

use Concrete\Core\Entity\Express\Control\AuthorControl;
use Concrete\Core\Entity\Express\Control\Control;
use Concrete\Core\Entity\Express\Entity;
use Concrete\Core\Entity\Express\Entry;
use Concrete\Core\Express\ObjectManager;
use Concrete\Core\User\UserInfo;
use Symfony\Component\HttpFoundation\Request;

class AuthorSaveHandler implements SaveHandlerInterface
{
    /**
     * @param AuthorControl $control
     * @param Entry $entry
     * @param Request $request
     */
    public function saveFromRequest(Control $control, Entry $entry, Request $request)
    {
        $author = $request->request->get('author');
        if ($author) {
            $ui = UserInfo::getById($request->request->get('author'));
            $entry->setAuthor($ui->getEntityObject());
        }
    }
}
