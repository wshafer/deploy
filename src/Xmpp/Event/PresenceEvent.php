<?php

namespace Reliv\Deploy\Xmpp\Event;

use Reliv\Deploy\Command\CommandInterface;

class PresenceEvent extends XmppEventAbstract
{
    protected $jid;
    protected $type;
    protected $show;
    protected $stanza;

    /**
     * Event Constructor
     *
     * @param \JAXL            $client  Jaxl Client
     * @param CommandInterface $command Running Command
     * @param \XMPPStanza      $stanza  Original Jaxl Stanza
     * @param string           $jid     Jabber Id
     * @param string           $type    Presence Type
     * @param string|null      $show    Optional Show message
     */
    public function __construct(
        \JAXL $client,
        CommandInterface $command,
        \XMPPStanza $stanza,
        $jid,
        $type = 'available',
        $show = null
    ) {
        $this->jid = $jid;
        $this->stanza = $stanza;

        $this->setType($type);
        $this->setShow($show);

        parent::__construct($client, $command);
    }

    /**
     * Set the type
     *
     * @param string $type Type of presence message
     *
     * @return void
     */
    protected function setType($type)
    {
        if (empty($type)) {
            $type = 'available';
        }

        $allowed = array(
            'available',
            'unavailable',
            'subscribe',
            'subscribed',
            'unsubscribe',
            'unsubscribed',
            'probe',
            'error'
        );

        if (!in_array($type, $allowed)) {
            $type = 'unsupported';
        }

        $this->type = $type;
        return;
    }

    /**
     * Set show
     *
     * @param string $show Set the show value as per XMPP standard
     *
     * @return void
     */
    protected function setShow($show)
    {
        $allowed = array(
            'away',
            'chat',
            'dnd',
            'xa'
        );

        if (!empty($show) && !in_array($show, $allowed)) {
            $show = null;
        }

        $this->show = $show;
        return;
    }

    /**
     * @return string
     */
    public function getJid()
    {
        return $this->jid;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getShow()
    {
        return $this->show;
    }

    /**
     * @return \XMPPStanza
     */
    public function getStanza()
    {
        return $this->stanza;
    }


}
