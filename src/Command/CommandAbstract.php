<?php
/**
 * Command Abstract
 *
 * Command Abstract for all deploy commands
 *
 * PHP version 5.4
 *
 * LICENSE: No License yet
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
use Reliv\Deploy\Factory\LoggerFactory;
use Reliv\Deploy\Service\ConfigService;
use Symfony\Component\Console\Command\Command;

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

    /** @var LoggerFactory  */
    protected $loggerService;

    /** @var LoggerInterface  */
    protected $logger;


    /**
     * Constructor
     *
     * @param ConfigService $configService Config Service
     * @param LoggerFactory $loggerService Factory for logging service
     * @param string        $name          Command Name
     */
    public function __construct(
        ConfigService $configService,
        LoggerFactory $loggerService,
        $name = null
    ) {
        parent::__construct($name);
        $this->configService = $configService;
        $this->loggerService = $loggerService;
    }

    /**
     * Get the logger for the command
     *
     * @return \Monolog\Logger|LoggerInterface
     */
    protected function getCommandLogger()
    {
        if ($this->logger instanceof LoggerInterface) {
            return $this->logger;
        }

        return $this->loggerService->getLogger(
            'Command.'.$this->getName(),
            $this->getConfigService()->getDefaultConfig()
        );
    }

    /**
     * Get the config service
     *
     * @return ConfigService
     */
    protected function getConfigService()
    {
        return $this->configService;
    }

    /**
     * Get the logger service
     *
     * @return LoggerFactory
     */
    protected function getLoggerService()
    {
        return $this->loggerService;
    }
}
