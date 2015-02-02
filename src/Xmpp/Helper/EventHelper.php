<?php

namespace Reliv\Deploy\Xmpp\Helper;

use Monolog\Formatter\LineFormatter;
use Reliv\Deploy\Xmpp\Event\XmppEventInterface;
use Reliv\Deploy\Xmpp\Monolog\Handler\XmppHandler;
use Reliv\Deploy\Xmpp\Symfony\Output\XmppOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Zend\Config\Config;

class EventHelper
{
    protected $event;

    /**
     * Constructor
     *
     * @param XmppEventInterface $event XmppEvent
     */
    public function __construct(XmppEventInterface $event)
    {
        $this->event = $event;
    }

    /**
     * Start the Xmpp Logger
     *
     * @return void
     */
    public function startLogger()
    {
        /** @var Config $notificationConfig */
        $notificationConfig = $this->getNotificationConfig();

        /** @var Config $recipientList */
        $recipientList = $this->getRecipients();
        $defaultReporting = $notificationConfig->get('defaultReportingLevel', null);

        $logHandler = new XmppHandler(
            $this->getClient(),
            $recipientList->toArray(),
            $defaultReporting
        );

        /** @var Config $loggerConfig */
        $loggerConfig = $this->getLoggerConfig();
        $logFormat = $loggerConfig->get('logFormat', null);
        $dateFormat = $this->getDateFormat();

        /** @param $formatter \Monolog\Formatter\LineFormatter */
        $formatter = new LineFormatter($logFormat, $dateFormat, true, true);

        $logHandler->setFormatter($formatter);

        $this->event->getLoggerService()->pushHandlerToAllLoggers($logHandler);
    }

    public function startCron()
    {
        $cronJobs = $this->getCronConfig();

        foreach ($cronJobs as $job) {
            if (empty($job['command']) || empty($job['delay'])) {
                continue;
            }

            $helper = $this;

            \JAXLLoop::$clock->call_fun_periodic(
                $job['delay'],
                function () use ($helper, $job) {
                    $helper->runCommand($this->getRecipients(), $job['command']);
                }
            );
        }
    }

    public function processChatMessage($from, $message) {
        $message = $this->filterMessage($message);

        $parsedMessage = explode(" ", $message);
        $sortedMessage = array_reverse($parsedMessage);

        $hostname = strtolower(array_pop($sortedMessage));
        $command = array_pop($sortedMessage);
        $args = $sortedMessage;

        $myHostname = gethostname();

        $pattern = '/^'.$hostname.'.+/i';

        if (!preg_match($pattern, $myHostname) && $hostname != 'all') {
            $this->getClient()->send_chat_msg($from, "Hostname did NOT match");
            return;
        }

        $this->runCommand($from, $command, $args);
    }

    protected function filterMessage($message)
    {
        $message = trim(preg_replace('/[\s\t\n\r\s]+/', ' ', $message));

        return $message;
    }

    /**
     * Get the main config
     *
     * @return \Zend\Config\Config
     */
    public function getConfig()
    {
        return $this->event->getConfigService()->getMainConfig();
    }

    /**
     * Get the default config
     *
     * @return \Zend\Config\Config
     */
    public function getDefaultConfig()
    {
        return $this->event->getConfigService()->getDefaultConfig();
    }

    /**
     * Get the config for Xmpp
     *
     * @return \Zend\Config\Config
     */
    public function getXmppConfig()
    {
        $config = $this->getConfig();
        return $config->get('xmpp', new Config(array()));
    }

    /**
     * Get the notification config
     *
     * @return \Zend\Config\Config
     */
    public function getNotificationConfig()
    {
        $xmppConfig = $this->getXmppConfig();
        return $xmppConfig->get('notifications', new Config(array()));
    }

    /**
     * Get the logger Config
     *
     * @return \Zend\Config\Config
     */
    public function getLoggerConfig()
    {
        $xmppConfig = $this->getXmppConfig();
        return $xmppConfig->get('logger', new Config(array()));
    }

    /**
     * Get the cron config
     *
     * @return \Zend\Config\Config
     */
    public function getCronConfig()
    {
        $xmppConfig = $this->getXmppConfig();
        return $xmppConfig->get('cron', new Config(array()));
    }

    /**
     * Get Recipients List
     *
     * @return \Zend\Config\Config
     */
    public function getRecipients()
    {
        $notificationConfig = $this->getNotificationConfig();
        return $notificationConfig->get('recipients', new Config(array()));
    }

    /**
     * Get the systems configured date format
     *
     * @return mixed
     */
    public function getDateFormat()
    {
        return $this->event->getConfigService()->getDateFormat();
    }

    /**
     * Get the current Xmpp Client
     *
     * @return \JAXL
     */
    public function getClient()
    {
        return $this->event->getClient();
    }

    /**
     * Run another command using Xmpp as the output layer
     *
     * @param string $outputTo Send output to
     * @param string $command  Command to run
     * @param array  $args     Arguments to pass to command
     *
     * @return void
     */
    public function runCommand($outputTo, $command, Array $args = array())
    {
        $args = array_merge(array('deploy', $command), $args);
        $input = new ArgvInput($args);

        $output = new XmppOutput($this->getClient(), $outputTo);
        $output->write("Starting command: ".$command);
        $this->event->getCommand()->runAdditionalCommand($input, $output);
    }
}
