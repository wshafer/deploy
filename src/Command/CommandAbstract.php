<?php
/**
 * Command Abstract
 *
 * Command Abstract for all deploy commands
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
namespace Reliv\Deploy\Command;

use Psr\Log\LoggerInterface;
use Reliv\Deploy\Service\LoggerService;
use Reliv\Deploy\Service\ConfigService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Command Abstract
 *
 * Command Abstract for all deploy commands
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
abstract class CommandAbstract extends Command implements CommandInterface
{
    /** @var ConfigService  */
    protected $configService;

    /** @var LoggerService  */
    protected $loggerService;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    /** @var LoggerInterface  */
    protected $logger;


    /**
     * Constructor
     *
     * @param ConfigService   $configService   Config Service
     * @param LoggerService   $loggerService   Factory for logging service
     * @param EventDispatcher $eventDispatcher Event Dispatcher
     * @param string          $name            Command Name
     */
    public function __construct(
        ConfigService $configService,
        LoggerService $loggerService,
        EventDispatcher $eventDispatcher,
        $name = null
    ) {
        parent::__construct($name);
        $this->configService = $configService;
        $this->loggerService = $loggerService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get the logger for the command
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getCommandLogger()
    {
        if ($this->logger instanceof LoggerInterface) {
            return $this->logger;
        }

        $this->logger = $this->loggerService->getLogger('Command.'.$this->getName());

        return $this->logger;
    }

    /**
     * Get the config service
     *
     * @return ConfigService
     */
    public function getConfigService()
    {
        return $this->configService;
    }

    /**
     * Get the logger service
     *
     * @return LoggerService
     */
    public function getLoggerService()
    {
        return $this->loggerService;
    }

    /**
     * Get the console event dispatcher
     *
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Run an additional command
     *
     * @param InputInterface  $input  Input args
     * @param OutputInterface $output Output Handler
     *
     * @return void
     */
    public function runAdditionalCommand(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();
        $commandName = $input->getFirstArgument();

        try {
            $application->get($commandName);
        } catch (\InvalidArgumentException $e) {
            $this->getCommandLogger()->warning('Command '.$commandName.' does not exist.');

            $input = new ArrayInput(array('command' => 'help'));
        }

        $this->getCommandLogger()->debug('Running Command '.$commandName);

        try {
            $application->doRun($input, $output);
        } catch (\Exception $e) {
            $this->getCommandLogger()->error(
                'Exception thrown while running command: '.$commandName
                .' in file '.$e->getFile().' on line '.$e->getLine().' Message: '.$e->getMessage()
            );
        }
    }
}
