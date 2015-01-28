<?php
/**
 * Command Interface
 *
 * Command Interface for all deploy commands
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

use Reliv\Deploy\Factory\LoggerFactory;
use Reliv\Deploy\Service\ConfigService;

/**
 * Command Interface
 *
 * Command Interface for all deploy commands
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
interface CommandInterface
{
    /**
     * Constructor for commands
     *
     * @param ConfigService $configService Config Service
     * @param LoggerFactory $loggerService Logger Service
     * @param string        $name          Name of command
     */
    public function __construct(ConfigService $configService, LoggerFactory $loggerService, $name = null);
}
