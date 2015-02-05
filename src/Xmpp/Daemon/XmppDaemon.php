<?php

namespace Reliv\Deploy\Xmpp\Daemon;

use Reliv\Deploy\Daemon\DaemonAbstract;
use Reliv\Deploy\Xmpp\Event\ChatEvent;
use Reliv\Deploy\Xmpp\Event\PresenceEvent;
use Reliv\Deploy\Xmpp\Event\XmppEvents;
use Reliv\Deploy\Xmpp\Event\AuthEvent;
use Reliv\Deploy\Xmpp\Event\ConnectEvent;
use Reliv\Deploy\Xmpp\Helper\CronHelper;
use Zend\Config\Config;

class XmppDaemon extends DaemonAbstract
{
    /**
     * @var \JAXL
     */
    protected $client;

    /**
     * @var Config
     */
    protected $config;

    protected $cronHelper;


    /**
     * Start Daemon
     *
     * @return void
     */
    public function run()
    {
        $this->startClient();
    }

    /**
     * Get the current Client
     *
     * @return \JAXL
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = $this->startClient();
        }

        return $this->client;
    }

    /**
     * Start the Client
     *
     * @return \JAXL
     */
    public function startClient()
    {
        $config = $this->getConfig();

        $this->client = new \JAXL($config->get('connection', new Config(array()))->toArray());

        $client = $this->client;

        /*
         * This is a Gotcha!  Because we set our own call back handler Jaxl will not
         * start the stream.  To make matters worse, Jaxl's default start_stream does
         * not return the correct data. To get around all of this we're going to
         * register our callback first and then set Jaxl's start_stream to run second.
         */
        $client->add_cb('on_connect', array($this, 'onConnect'), 1);
        $client->add_cb('on_connect', array($this->client, 'start_stream'), 2);

        $client->add_cb('on_connect_error', array($this, 'onConnectError'));

        /*
         * Documentation exists for on_stream_start but this event is never
         * fired, ever.  Leaving in here for documentation.
         */
//        $client->add_cb('on_stream_start', array($this, 'onStreamStart'));

        /*
         * Documentation exists for on_stream_features but setting this callback
         * throws an exception.  Leaving here for documentation.
         */
        //$client->add_cb('on_stream_features', array($this, 'onStreamFeatures'));

        $client->add_cb('on_auth_success', array($this, 'onAuth'));
        $client->add_cb('on_auth_failure', array($this, 'onAuthFailure'));

        $client->add_cb('on_presence_stanza', array($this, 'onPresenceStanza'));

        $client->add_cb('on_chat_message', array($this, 'onChatMessage'));

        /*
         * The following callbacks are available in Jaxl, but will are not implimented
         * in this deploy script.
         */
//        $client->add_cb('on_groupchat_message', array($this, 'onChatMessage'));
//        $client->add_cb('on_headline_message', array($this, 'onChatMessage'));
//        $client->add_cb('on_normal_message', array($this, 'onChatMessage'));
//        $client->add_cb('on_error_message', array($this, 'onChatMessage'));

//

//
//        $client->add_cb('on_disconnect', array($this, 'onDisconnect'));
//
        $client->start($config->get('start', new Config(array()))->toArray());

        return $client;
    }

    /**
     * On Connect Callback for Jaxl.  Triggers XmppEvents::CONNECT deploy event
     *
     * @return array
     */
    public function onConnect()
    {
        $eventDispatcher = $this->getEventDispatcher();
        $event = new ConnectEvent(
            $this->getClient(),
            $this->getCommand(),
            $this->getCronHelper(),
            true
        );

        $eventDispatcher->dispatch(XmppEvents::CONNECT, $event);

        if ($event->isPropagationStopped()) {
            $this->getClient()->send_end_stream();
        }

        return array();
    }

    /**
     * On Connect Error Callback for Jaxl.  Triggers XmppEvents::CONNECT deploy event
     *
     * @param integer $errorNumber  Error number supplied in via Jaxl
     * @param string  $errorMessage Error message supplied by Jaxl
     *
     * @return array
     */
    public function onConnectError($errorNumber, $errorMessage)
    {
        $eventDispatcher = $this->getEventDispatcher();
        $event = new ConnectEvent(
            $this->getClient(),
            $this->getCommand(),
            $this->getCronHelper(),
            false,
            $errorNumber,
            $errorMessage
        );

        $eventDispatcher->dispatch(XmppEvents::CONNECT, $event);

        return array($errorNumber, $errorMessage);
    }

    /**
     * On Auth Success Callback for Jaxl.  Triggers XmppEvents::AUTH deploy event
     *
     * @return array
     */
    public function onAuth()
    {
        $eventDispatcher = $this->getEventDispatcher();
        $event = new AuthEvent(
            $this->getClient(),
            $this->getCommand(),
            $this->getCronHelper(),
            true
        );

        $eventDispatcher->dispatch(XmppEvents::AUTH, $event);

        return array();
    }

    /**
     * On Auth Failure Callback for Jaxl.  Triggers XmppEvents::AUTH deploy event
     *
     * @param string $reason Reason Message returned from JAXL
     *
     * @return array
     */
    public function onAuthFailure($reason)
    {
        $eventDispatcher = $this->getEventDispatcher();
        $event = new AuthEvent(
            $this->getClient(),
            $this->getCommand(),
            $this->getCronHelper(),
            false,
            $reason
        );

        $eventDispatcher->dispatch(XmppEvents::AUTH, $event);

        return array($reason);
    }

    /**
     * On Presence Stanza Callback
     *
     * @param \XMPPStanza $stanza Stanza object returned from JAXL
     *
     * @return void
     */
    public function onPresenceStanza(\XMPPStanza $stanza)
    {
        $eventDispatcher = $this->getEventDispatcher();
        $event = new PresenceEvent(
            $this->getClient(),
            $this->getCommand(),
            $this->getCronHelper(),
            $stanza,
            $stanza->from,
            $stanza->type,
            $stanza->show
        );

        $eventDispatcher->dispatch(XmppEvents::PRESENCE_STANZA, $event);
    }

    /**
     * On Chat Message Callback
     *
     * @param \XMPPStanza $stanza Stanza object returned from JAXL
     *
     * @return void
     */
    public function onChatMessage($stanza)
    {
        /*
         * Xmpp sends a message for every letter typed, but not the message until one hits return
         * So we're going to do nothing on a blank message.
         */
        $message = $stanza->body;

        if (empty($message)) {
            return;
        }

        $eventDispatcher = $this->getEventDispatcher();
        $event = new ChatEvent(
            $this->getClient(),
            $this->getCommand(),
            $this->getCronHelper(),
            $stanza,
            'chat',
            $stanza->from,
            $message
        );

        $eventDispatcher->dispatch(XmppEvents::CHAT_MESSAGE, $event);
    }



    /**
     * On Disconnect Callback
     *
     * @return void
     */
    public function onDisconnect()
    {
        _info("got on_disconnect cb");
    }

    /**
     * Get the XMPP config
     *
     * @return Config
     */
    public function getConfig()
    {
        if (!$this->config) {
            $this->config = $this->getConfigService()
                ->getMainConfig()
                ->get('xmpp', new Config(array()));
        }

        return $this->config;
    }

    /**
     * Get the cron helper for XMPP
     *
     * @return CronHelper
     */
    public function getCronHelper()
    {
        if (!$this->cronHelper) {
            $this->cronHelper = new CronHelper($this->getConfig(), $this->getClient(), $this->getCommand());
        }

        return $this->cronHelper;
    }
}
