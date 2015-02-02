<?php
/**
 * Vcs Interface for Version Control Drivers.
 *
 * Vcs Interface for Version Control Drivers.
 *
 * PHP version 5.4
 *
 * LICENSE: BSD
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      http://github.com/reliv
 */
namespace Reliv\Deploy\Vcs;

use Reliv\Deploy\Service\LoggerService;
use Zend\Config\Config;

/**
 * Vcs Interface for Version Control Drivers.
 *
 * Vcs Interface for Version Control Drivers.
 *
 * PHP version 5.4
 *
 * LICENSE: BSD
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      https://github.com/reliv
 */
interface VcsInterface
{
    /**
     * Set the name of the Repo.  This will be called when constructing the class.
     *
     * @param string $name Name of repository
     *
     * @return mixed
     */
    public function setName($name);

    /**
     * Set config.  This will be called when constructing the class.
     *
     * @param Config $config Repository Config
     *
     * @return void
     */
    public function setConfig(Config $config);

    /**
     * Set the Applications Current Release Directory.  This will be called when constructing the class.
     *
     * @param string $dir Path to the Applications Current Release Directory.
     *
     * @return void
     */
    public function setCurrentReleaseAppDir($dir);

    /**
     * Set the target directory for the next release.  This will be called when constructing the class.
     *
     * @param string $dir Target directory for the next release
     *
     * @return void
     */
    public function setNextReleaseAppDir($dir);

    /**
     * Set a PSR3 logger to use during deployment.  This will be called when constructing the class.
     *
     * @param LoggerService $loggerService PSR3 compatible logger
     *
     * @return void
     */
    public function setLoggerService(LoggerService $loggerService);

    /**
     * Get the current Deployment status
     *
     * @return mixed
     */
    public function getStatus();

    /**
     * Does the application need an update from the repository?
     *
     * @return bool
     */
    public function needsUpdate();

    /**
     * Preform the update.
     *
     * @return string Revision number updated to
     */
    public function update();
}
