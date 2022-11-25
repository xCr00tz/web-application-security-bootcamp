<?php
namespace Concrete\Core\Conversation\Message;

class MessageEvent extends \Symfony\Component\EventDispatcher\GenericEvent
{
    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }
    public function getMessage()
    {
        return $this->message;
    }
}
