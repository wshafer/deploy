<?php
/**
 * Test for Deploy Command
 *
 * Test for Deploy Command
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

namespace Reliv\Deploy\Tests\Command;

use Monolog\Logger;
use Reliv\Deploy\Command\CommandAbstract;
use Reliv\Deploy\Command\Deploy;
use Reliv\Deploy\Factory\LoggerFactory;
use Reliv\Deploy\Service\ConfigService;
use Zend\Config\Config;

/**
 * Test for Deploy Command
 *
 * Test for Deploy Command
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class DeployTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Reliv\Deploy\Command\Deploy
     */
    public $command;

    /**
     * General setup for tests
     *
     * @return void
     */
    public function setup()
    {
        $mockConfigService = $this->getMockBuilder('\Reliv\Deploy\Service\ConfigService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLoggerFactory = $this->getMockBuilder('\Reliv\Deploy\Factory\LoggerFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLoggerFactory->expects($this->any())
            ->method('info');

        $mockLoggerFactory->expects($this->any())
            ->method('debug');

        $mockLoggerFactory->expects($this->any())
            ->method('notice');

        $this->command = new Deploy($mockConfigService, $mockLoggerFactory);
    }

    /**
     * Test the constructor
     *
     * @return void
     * @covers \Reliv\Deploy\Command\Deploy::__construct
     */
    public function testConstructor()
    {
        $this->assertTrue($this->command instanceof Deploy);
    }

    /**
     * Test Configure
     *
     * @return void
     * @covers \Reliv\Deploy\Command\Deploy::configure
     */
    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getName());
        $this->assertNotEmpty($this->command->getDescription());
    }
}
