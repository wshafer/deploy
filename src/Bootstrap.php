<?php
/**
 * Reliv Deploy Bootstrap
 *
 * Reliv Deploy Bootstrap
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
namespace Reliv\Deploy;

use Reliv\Deploy\Service\ConfigService;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Console\Application;
use Zend\Config\Config;
use Reliv\Deploy\Service\LoggerService;
use Monolog\ErrorHandler;

/**
 * Reliv Deploy Bootstrap
 *
 * Reliv Deploy Bootstrap.  Setup the application and all needed classes and objects needed to run.
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Bootstrap
{
    protected $configPaths;

    /* Place Holders */
    protected $configService;
    protected $loggerService;
    protected $input;
    protected $output;
    protected $application;
    protected $eventDispatcher;
    protected $appLogger;

    /**
     * Constructor
     *
     * @param array $configPaths Array of config paths to parse
     */
    public function __construct(Array $configPaths)
    {
        $this->configPaths = $configPaths;
    }

    /**
     * Start the application
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $this->registerErrorHandler();
        $this->registerCommands();
        $this->getApplication()->run($this->getInput(), $this->getOutput());
    }

    /**
     * Get the merged config
     *
     * @return Config
     */
    protected function getConfig()
    {
        return $this->getConfigService()->getMainConfig();
    }

    /**
     * Get the config service
     *
     * @return ConfigService
     */
    protected function getConfigService()
    {
        if (!$this->configService) {
            $this->configService = new ConfigService($this->configPaths);
        }

        return $this->configService;
    }

    /**
     * Get the input
     *
     * @return ArgvInput
     */
    protected function getInput()
    {
        if (!$this->input) {
            $this->input = new ArgvInput();
        }

        return $this->input;
    }

    /**
     * Get the console output
     *
     * @return ConsoleOutput
     */
    protected function getOutput()
    {
        if (!$this->output) {
            $this->output = new ConsoleOutput();
        }

        return $this->output;
    }

    /**
     * Get the application
     *
     * @return Application
     */
    protected function getApplication()
    {
        if (!$this->application) {
            $this->application = new Application();
            $this->application->setDispatcher($this->getEventDispatcher());
        }

        return $this->application;
    }

    /**
     * Get the event dispatcher
     *
     * @return EventDispatcher
     */
    protected function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->eventDispatcher = new EventDispatcher();

            $subscribers = $this->getEventSubscribers();

            foreach ($subscribers as $subscriberClass) {
                $this->eventDispatcher->addSubscriber($this->getSubscriber($subscriberClass));
            }
        }

        return $this->eventDispatcher;
    }

    /**
     * Get the event subscribers
     *
     * @return mixed
     */
    protected function getEventSubscribers()
    {
        $config = $this->getConfig();

        return $config->get('events', new Config(array()))
            ->get('subscribers', new Config(array()));
    }

    /**
     * Get a subscriber
     *
     * @param string $class Class to load
     *
     * @return mixed
     */
    protected function getSubscriber($class)
    {
        if (!class_exists($class)) {
            trigger_error('Unable to find event subscriber:'.$class, E_USER_WARNING);
        }

        $class = new $class;

        if (method_exists($class, 'setConfigService')) {
            $class->setConfigService($this->configService);
        }

        return $class;
    }

    /**
     * Register commands for Symfony Console
     *
     * @return void
     */
    protected function registerCommands()
    {
        // Add Commands to CLI
        $commands = $this->getConfigService()->getCommands();
        $appLogger = $this->getApplicationLogger();
        $application = $this->getApplication();

        $appLogger->debug('Configured Commands: '.print_r($commands->toArray(), true));

        foreach ($commands as $commandClass) {
            if (!class_exists($commandClass)) {
                $appLogger->warning('Class not found or not loaded for '.$commandClass);
                $appLogger->debug('Skipping '.$commandClass);
                continue;
            }

            if (!in_array('Reliv\Deploy\Command\CommandInterface', class_implements($commandClass))) {
                $appLogger->warning(
                    'Class '.$commandClass.' is not an instance of \Reliv\Deploy\Command\CommandInterface'
                );

                $appLogger->debug('Skipping '.$commandClass);
                continue;
            }

            $command = new $commandClass(
                $this->getConfigService(),
                $this->getLoggerService(),
                $this->getEventDispatcher()
            );

            $appLogger->debug('Adding Command: '.$commandClass);
            $application->add($command);
        }
    }

    /**
     * Get the logger factory
     *
     * @return LoggerService
     */
    protected function getLoggerService()
    {
        if (!$this->loggerService) {
            $this->loggerService = new LoggerService($this->getOutput(), $this->getConfig());
        }

        return $this->loggerService;
    }

    /**
     * Get the main application logger.  Should really only be used internally to this class.
     *
     * @return \Monolog\Logger
     */
    protected function getApplicationLogger()
    {
        if (!$this->appLogger) {
            $this->appLogger = $this->getLoggerService()
                ->getLogger(
                    'Application',
                    $this->getConfigService()->getDefaultConfig()
                );
        }

        return $this->appLogger;
    }

    /**
     * Register the error handler
     *
     * @return void
     */
    protected function registerErrorHandler()
    {
        ErrorHandler::register($this->getApplicationLogger());
    }
}
