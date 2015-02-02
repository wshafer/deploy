<?php
/**
 * Script Runner
 *
 * This file contains the Script Runner Service Provider
 *
 * PHP version 5.4
 *
 * LICENSE: BSD
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      https://github.com/reliv
 */

namespace Reliv\Deploy\Service;

/**
 * Script Runner
 *
 * This file will run external php programs inside their own bubble.  This keeps external apps from interfering with
 * the operation of the main application.
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
class ScriptRunner
{
    /**
     * Run an external Script
     *
     * @param string $scriptPath Path to script
     * @param array  $passedVars Variables to pass to the external script
     *
     * @return mixed
     */
    public function runScript($scriptPath, Array $passedVars = array())
    {
        if (!file_exists($scriptPath)) {
            throw new \InvalidArgumentException(
                'Unable to run external script. No script found at '.$scriptPath.'.'
            );
        }

        foreach ($passedVars as $key => $value) {
            $$key = $value;
        }

        $return = include $scriptPath;

        return $return;
    }
}
