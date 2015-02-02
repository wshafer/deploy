<?php

namespace Reliv\Deploy\Xmpp\Event;

use Reliv\Deploy\Command\CommandInterface;

class ChatEvent extends XmppEventAbstract
{
    protected $stanza;
    protected $type;
    protected $from;
    protected $message;


    /**
     * Event Constructor
     *
     * @param \JAXL            $client  Jaxl Client
     * @param CommandInterface $command Running Command
     * @param \XMPPStanza      $stanza  Original Jaxl Stanza
     * @param string           $type    Type of chat message.
     * @param string           $from    From Jabber Id
     * @param string           $message Message
     */
    public function __construct(
        \JAXL $client,
        CommandInterface $command,
        \XMPPStanza $stanza,
        $type,
        $from,
        $message
    ) {
        $this->stanza = $stanza;
        $this->setType($type);
        $this->from = $from;
        $this->message = trim($message);

        parent::__construct($client, $command);
    }

    protected function setType($type)
    {
        $allowed = array(
            'chat',
            'groupchat',
            'headline',
            'normal',
            'error'
        );

        if (empty($type)) {
            throw new \RuntimeException(
                "Message type must be supplied"
            );
        }

        if (!in_array($type, $allowed)) {
            throw new \RuntimeException(
                "Message type not supported.  Current message types supported are: ".implode(', ', $allowed)
            );
        }

        $this->type = $type;
    }

    /**
     * @return \XMPPStanza
     */
    public function getStanza()
    {
        return $this->stanza;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
