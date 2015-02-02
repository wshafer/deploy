<?php

namespace Reliv\Deploy\Xmpp\EventSubscriber;

use Monolog\Formatter\LineFormatter;
use Reliv\Deploy\EventSubscriber\EventSubscriberAbstract;
use Reliv\Deploy\Xmpp\Event\AuthEvent;
use Reliv\Deploy\Xmpp\Event\ChatEvent;
use Reliv\Deploy\Xmpp\Event\PresenceEvent;
use Reliv\Deploy\Xmpp\Event\XmppEvents;
use Reliv\Deploy\Xmpp\Helper\EventHelper;
use Reliv\Deploy\Xmpp\Monolog\Handler\XmppHandler;
use Reliv\Deploy\Xmpp\Symphony\Output\XmppOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Zend\Config\Config;

class XmppEventSubscriber extends EventSubscriberAbstract
{
    public static function getSubscribedEvents()
    {
        return array(
            XmppEvents::AUTH  => array('onAuthSuccess', 0),
            XmppEvents::CHAT_MESSAGE => array('onChatMessage', 0)
        );
    }

    public function onAuthSuccess(AuthEvent $event)
    {
        if (!$event->isAuthenticated()) {
            return;
        }

        $client = $event->getClient();
        $client->get_roster();
        $client->get_vcard();
        $client->set_status("available");
        $client->set_status("unavailable");

        $eventHelper = new EventHelper($event);
        $eventHelper->startLogger();
        $eventHelper->startCron();
    }

    public function onChatMessage(ChatEvent $event)
    {
        $eventHelper = new EventHelper($event);
        $eventHelper->processChatMessage($event->getFrom(), $event->getMessage());
    }
}
