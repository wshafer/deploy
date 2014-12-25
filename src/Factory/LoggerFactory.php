<?php

namespace Reliv\Deploy\Factory;

use Reliv\Deploy\Monolog\Formatter\SymfonyConsoleFormatter;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Config\Config;

class LoggerFactory
{
    protected $output;

    protected $verbosityLevelMap = array(
        OutputInterface::VERBOSITY_DEBUG        => Logger::DEBUG,
        OutputInterface::VERBOSITY_NORMAL       => Logger::ERROR,
        OutputInterface::VERBOSITY_VERBOSE      => Logger::NOTICE,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::INFO,
    );

    protected $formatLevelMap = array(
        Logger::INFO => '',
        Logger::NOTICE => SymfonyConsoleFormatter::INFO,
    );

    protected $logFormat  = ConsoleFormatter::SIMPLE_FORMAT;
    //protected $dateFormat = ConsoleFormatter::SIMPLE_DATE;
    protected $dateFormat = 'm-d-Y';

    public function __construct($output)
    {
        $this->output = $output;
    }

    public function getLogger($name, Config $config)
    {
        // create a log channel
        $logger = new Logger($name);
        $consoleHandler = $this->getDefaultConsoleHandler($config);
        $logger->pushHandler($consoleHandler);

        return $logger;
    }

    protected function getDefaultConsoleHandler(Config $config)
    {
        $logFormat = $this->logFormat;
        if (!empty($config['Logger']['console']['logFormat'])) {
            $logFormat = $config['Logger']['console']['logFormat'];
        }

        $dateFormat = $this->dateFormat;
        if (!empty($config['Logger']['console']['dateFormat'])) {
            $dateFormat = $config['Logger']['console']['dateFormat'];
        }

        $verbosityLevelMap = $this->verbosityLevelMap;
        if (!empty($config['Logger']['console']['verbosityLevelMap'])) {
            $verbosityLevelMap = $config['Logger']['console']['verbosityLevelMap'] + $this->verbosityLevelMap;
        }

        $formatLevelMap = $this->formatLevelMap;
        if (!empty($config['Logger']['console']['formatLevelMap'])) {
            $verbosityLevelMap = $config['Logger']['console']['formatLevelMap'] + $this->formatLevelMap;
        }

        $consoleHandler = new ConsoleHandler($this->output, true, $verbosityLevelMap);
        $consoleFormatter = new SymfonyConsoleFormatter(
            $logFormat,
            $dateFormat,
            true,
            true,
            $formatLevelMap
        );

        $consoleHandler->setFormatter($consoleFormatter);
        return $consoleHandler;
    }
}