<?php

namespace Reliv\Deploy\Xmpp\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class XmppHandler extends AbstractProcessingHandler
{
    const DEBUG = Logger::DEBUG;
    const INFO = Logger::INFO;
    const NOTICE = Logger::NOTICE;
    const WARNING = Logger::WARNING;
    const ERROR = Logger::ERROR;

    /**
     * @var \JAXL
     */
    protected $client;

    /**
     * @var array
     */
    protected $recipientList = array();

    protected $defaultReporting;

    public function __construct(\JAXL $client, Array $recipientList, $level = XmppHandler::DEBUG, $bubble = true)
    {

        if (!is_numeric($level)) {
            $level = constant('SELF::'.strtoupper($level));
        }

        if (!$level) {
            $level = XmppHandler::DEBUG;
        }

        $this->recipientList = $recipientList;
        $this->client = $client;
        $this->defaultReporting = $level;

        parent::__construct(XmppHandler::DEBUG, $bubble);
    }

    protected function write(array $record)
    {
        foreach ($this->recipientList as $recipient) {

            if (empty($recipient['jid'])) {
                continue;
            }

            $reportingLevel = $this->defaultReporting;

            if (!empty($recipient['reportingLevel'])) {
                $reportingLevel = constant('SELF::'.strtoupper($recipient['reportingLevel']));
            }

            if ($record['level'] >= $reportingLevel) {
                $message = (string) $record['formatted'];
                $this->client->send_chat_msg($recipient['jid'], htmlentities($message));
            }
        }
    }

    public function handle(array $record)
    {
        $record = $this->processRecord($record);

        $record['formatted'] = $this->getFormatter()->format($record);

        $this->write($record);

        return false === $this->bubble;
    }
}