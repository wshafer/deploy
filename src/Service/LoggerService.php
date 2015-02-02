<?php
/**
 * Monolog Logger Service
 *
 * Monolog Logger Service
 *
 * PHP version 5.4
 *
 * LICENSE: License.txt New BSD License
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      http://github.com/reliv
 */
namespace Reliv\Deploy\Service;

use Monolog\Handler\HandlerInterface;
use Reliv\Deploy\Monolog\Formatter\SymfonyConsoleFormatter;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Config\Config;

/**
 * Monolog Logger Service
 *
 * Monolog Logger Service
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class LoggerService
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Config
     */
    protected $config;


    /**
     * @var array
     */
    protected $loggers;

    protected $extraHandlers = array();

    /**
     * @var array Verbosity Map.  Used to map MonLogs error level to the consoles verbosity settings
     */
    protected $verbosityLevelMap = array(
        OutputInterface::VERBOSITY_DEBUG        => Logger::DEBUG,
        OutputInterface::VERBOSITY_NORMAL       => Logger::ERROR,
        OutputInterface::VERBOSITY_VERBOSE      => Logger::NOTICE,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::INFO,
    );

    /**
     * @var array Format Level Map. Used to match Monologs error level to color formatting.
     */
    protected $formatLevelMap = array(
        Logger::INFO => '',
        Logger::NOTICE => SymfonyConsoleFormatter::INFO,
    );

    /**
     * @var string Log format
     */
    protected $logFormat  = ConsoleFormatter::SIMPLE_FORMAT;

    /**
     * @var string Date format.
     */
    protected $dateFormat = ConsoleFormatter::SIMPLE_DATE;

    /**
     * Constructor
     *
     * @param OutputInterface $output Console Output
     * @param Config          $config Deploy config object
     */
    public function __construct(OutputInterface $output, Config $config)
    {
        $this->output = $output;
        $this->config = $config;
    }

    /**
     * Get a logger and open a Monolog channel.
     *
     * @param string $name Channel name
     *
     * @return Logger
     */
    public function getLogger($name)
    {
        if (empty($this->loggers[$name])) {
            $logger = new Logger($name);
            $consoleHandler = $this->getDefaultConsoleHandler();
            $logger->pushHandler($consoleHandler);

            foreach ($this->extraHandlers as $extraHandler) {
                $logger->pushHandler($extraHandler);
            }

            $this->loggers[$name] = $logger;
        }

        return $this->loggers[$name];
    }

    public function pushHandlerToAllLoggers(HandlerInterface $handler)
    {
        /** @param \Monolog\Logger $logger */
        foreach ($this->loggers as $logger) {
            $logger->pushHandler($handler);
        }

        $this->extraHandlers[] = $handler;
    }

    /**
     * Get Default Console Handler for Monolog
     *
     * @return ConsoleHandler
     */
    protected function getDefaultConsoleHandler()
    {
        $logFormat = $this->logFormat;
        $config = $this->getConfig();

        if (!empty($config['console']['logger']['logFormat'])) {
            $logFormat = $config['console']['logger']['logFormat'];
        } elseif (!empty($config['default']['logger']['logFormat'])) {
            $logFormat = $config['default']['logger']['logFormat'];
        }

        $dateFormat = $this->dateFormat;
        if (!empty($config['default']['dateFormat'])) {
            $dateFormat = $config['default']['dateFormat'];
        }

        $verbosityLevelMap = $this->verbosityLevelMap;
        if (!empty($config['console']['logger']['verbosityLevelMap'])) {
            $verbosityLevelMap = $config['console']['logger']['verbosityLevelMap'] + $this->verbosityLevelMap;
        } elseif (!empty($config['default']['logger']['verbosityLevelMap'])) {
            $verbosityLevelMap = $config['default']['logger']['verbosityLevelMap'] + $this->verbosityLevelMap;
        }

        $formatLevelMap = $this->formatLevelMap;
        if (!empty($config['console']['logger']['formatLevelMap'])) {
            $verbosityLevelMap = $config['console']['logger']['formatLevelMap'] + $this->formatLevelMap;
        } elseif (!empty($config['default']['logger']['formatLevelMap'])) {
            $verbosityLevelMap = $config['default']['logger']['formatLevelMap'] + $this->formatLevelMap;
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

    /**
     * Get Logger Config
     *
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }
}
