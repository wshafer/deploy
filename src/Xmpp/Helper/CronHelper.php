<?php

namespace Reliv\Deploy\Xmpp\Helper;

use Reliv\Deploy\Command\CommandInterface;
use Reliv\Deploy\Xmpp\Symfony\Output\XmppOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Zend\Config\Config;

class CronHelper
{
    public $jobs = array();
    public $cronRunning = false;
    public $config;
    public $client;
    public $command;

    /**
     * Constructor
     *
     * @param Config           $config  Xmpp Config
     * @param \JAXL            $client  Xmpp Client
     * @param CommandInterface $command Running Symfony command
     */
    public function __construct(Config $config, \JAXL $client, CommandInterface $command)
    {
        $this->config = $config;
        $this->client = $client;
        $this->command = $command;
    }

    /**
     * Send an IM Message
     *
     * @param string   $sendTo  JID to send message to
     * @param $message $message Message to send
     *
     * @return void
     */
    public function sendMessage($sendTo, $message)
    {
        if (empty($sendTo) || is_array($sendTo)) {
            return;
        }

        $this->getClient()->send_chat_msg($sendTo, gethostname().' - '.$message);
    }

    /**
     * Additional commands for daemon
     *
     * @param string $action Action to preform
     * @param string $from   Message sent from
     *
     * @return void
     */
    public function cronCommand($action, $from = null)
    {
        if ($action == 'start') {
            $this->startCron();
            $this->getClient()->send_chat_msg($from, gethostname().' - Cron started');
        } elseif ($action == 'stop') {
            $this->stopCron();
            $this->getClient()->send_chat_msg($from, gethostname().' - Cron stopped');
        } elseif ($action == 'show') {
            $activeJobs = $this->getActiveJobs();

            foreach ($activeJobs as $job) {
                $this->getClient()->send_chat_msg(
                    $from,
                    gethostname().' - Job: '.$job['command'].' running'
                );
            }

        } else {
            $this->getClient()->send_chat_msg($from, gethostname().' - Unknown action.');
        }
    }

    /**
     * Start the cron
     *
     * @return void
     */
    public function startCron()
    {
        if (!empty($this->jobs)) {
            return;
        }

        $cronJobs = $this->getCronConfig();

        foreach ($cronJobs as $job) {
            if (empty($job['command']) || empty($job['delay'])) {
                continue;
            }

            $helper = $this;

            $ref = \JAXLLoop::$clock->call_fun_periodic(
                $job['delay'],
                function () use ($helper, $job) {
                    $helper->runCommand(null, $job['command']);
                }
            );


            $this->jobs[] = array(
                'command' => $job['command'],
                'ref' => $ref
            );
        }
    }

    /**
     * Stop the cron
     *
     * @return void
     */
    public function stopCron()
    {
        if (empty($this->jobs)) {
            return;
        }

        \JAXLLoop::$clock->jobs = array();
        $this->jobs = array();
    }

    /**
     * Get a list of the running jobs
     * 
     * @return array
     */
    public function getActiveJobs()
    {
        if (empty($this->jobs)) {
            return array();
        }

        return $this->jobs;
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
     * Get the Xmpp Client
     *
     * @return \JAXL
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get the XMPP Config
     *
     * @return Config
     */
    public function getXmppConfig()
    {
        return $this->config;
    }

    /**
     * Get the running command
     *
     * @return CommandInterface
     */
    public function getCommand()
    {
        return $this->command;
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
        $this->sendMessage($outputTo, "Starting command: ".$command);

        $args = array_merge(array('deploy', $command), $args);
        $input = new ArgvInput($args);

        $output = new XmppOutput($this->getClient(), $outputTo);
        $this->getCommand()->runAdditionalCommand($input, $output);

        $this->sendMessage($outputTo, "Command: ".$command." complete");
    }
}
