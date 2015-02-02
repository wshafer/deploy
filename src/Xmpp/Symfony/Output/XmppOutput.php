<?php

namespace Reliv\Deploy\Xmpp\Symfony\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;

class XmppOutput extends Output
{
    /**
     * @var \JAXL
     */
    protected $client;

    /**
     * @var array
     */
    protected $recipientList;


    public function __construct(
        \JAXL $client,
        $recipientList,
        $verbosity = self::VERBOSITY_VERY_VERBOSE,
        $decorated = false,
        OutputFormatterInterface $formatter = null
    ) {
        $this->client = $client;
        $this->recipientList = $recipientList;

        parent::__construct($verbosity, $decorated, $formatter);
    }

    /**
     * Writes a message to the output.
     *
     * @param string $message A message to write to the output
     * @param bool   $newline Whether to add a newline or not
     *
     * @return void
     */
    protected function doWrite($message, $newline)
    {
        $recipients = $this->getRecipients();

        foreach ($recipients as $recipient) {
            $this->client->send_chat_msg(
                $recipient,
                $message.($newline ? PHP_EOL : '')
            );
        }
    }

    protected function getRecipients()
    {
        if (empty($this->recipientList)) {
            return array();
        }

        $return = array();

        if (!is_array($this->recipientList)) {
            $return[] = $this->recipientList;
            return $return;
        }

        foreach ($this->recipientList as $recipient) {
            if (empty($recipient['jid'])) {
                continue;
            }

            $return[] = $recipient['jid'];
        }

        return $return;
    }
}
