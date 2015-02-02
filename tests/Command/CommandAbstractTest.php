<?php
/**
 * Test for Command Abstract
 *
 * Test for Command Abstract
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
use Reliv\Deploy\Factory\LoggerFactory;
use Reliv\Deploy\Service\ConfigService;

/**
 * Test for Command Abstract
 *
 * Test for Command Abstract.  Note: While most methods are protected, these methods serve as an interface
 * for extended classes.  Because of this, we will test these methods to ensure that the abstract is working
 * as expected.
 *
 * @category  Reliv
 * @package   Deploy
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class CommandAbstractTest extends \PHPUnit_Framework_TestCase
{
    public $commandAbstract;

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

        $this->commandAbstract = $this->getMockForAbstractClass(
            '\Reliv\Deploy\Command\CommandAbstract',
            array($mockConfigService, $mockLoggerFactory, 'abstract')
        );
    }

    /**
     * Test the constructor
     *
     * @return void
     * @covers \Reliv\Deploy\Command\CommandAbstract::__construct
     */
    public function testConstructor()
    {
        $this->assertTrue($this->commandAbstract instanceof CommandAbstract);
    }

    /**
     * Test the Get Command Logger.
     *
     * @return void
     * @covers \Reliv\Deploy\Command\CommandAbstract::getCommandLogger
     */
    public function testGetCommandLogger()
    {
        $mockConfigService = $this->getMockBuilder('\Reliv\Deploy\Service\ConfigService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConfig = $this->getMockBuilder('Zend\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConfigService->expects($this->once())
            ->method('getDefaultConfig')
            ->will($this->returnValue($mockConfig));

        $mockLoggerFactory = $this->getMockBuilder('\Reliv\Deploy\Factory\LoggerFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLogger = $this->getMockBuilder('\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLoggerFactory->expects($this->once())
            ->method('getLogger')
            ->will($this->returnValue($mockLogger));

        /**
         * @var \Reliv\Deploy\Command\CommandAbstract $commandAbstract
         */
        $commandAbstract = $this->getMockForAbstractClass(
            '\Reliv\Deploy\Command\CommandAbstract',
            array($mockConfigService, $mockLoggerFactory, 'abstract')
        );

        $reflectedClass = new \ReflectionClass($commandAbstract);
        $reflectedMethod = $reflectedClass->getMethod('getCommandLogger');
        $reflectedMethod->setAccessible(true);

        $logger = $reflectedMethod->invoke($commandAbstract);

        $this->assertTrue($logger instanceof Logger);
    }

    /**
     * Test the Get Command Logger Uses the created service on multiple calls
     *
     * @return void
     * @covers \Reliv\Deploy\Command\CommandAbstract::getCommandLogger
     */
    public function testGetCommandLoggerUsesCreatedLoggingServiceOnMultipleCalls()
    {
        $mockConfigService = $this->getMockBuilder('\Reliv\Deploy\Service\ConfigService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConfig = $this->getMockBuilder('Zend\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConfigService->expects($this->once())
            ->method('getDefaultConfig')
            ->will($this->returnValue($mockConfig));

        $mockLoggerFactory = $this->getMockBuilder('\Reliv\Deploy\Factory\LoggerFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLogger = $this->getMockBuilder('\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLoggerFactory->expects($this->once())
            ->method('getLogger')
            ->will($this->returnValue($mockLogger));

        /**
         * @var \Reliv\Deploy\Command\CommandAbstract $commandAbstract
         */
        $commandAbstract = $this->getMockForAbstractClass(
            '\Reliv\Deploy\Command\CommandAbstract',
            array($mockConfigService, $mockLoggerFactory, 'abstract')
        );

        $reflectedClass = new \ReflectionClass($commandAbstract);
        $reflectedMethod = $reflectedClass->getMethod('getCommandLogger');
        $reflectedMethod->setAccessible(true);

        $reflectedMethod->invoke($commandAbstract);
        $logger = $reflectedMethod->invoke($commandAbstract);

        $this->assertTrue($logger instanceof Logger);
    }

    /**
     * Test the Get Config Service.
     *
     * @return void
     * @covers \Reliv\Deploy\Command\CommandAbstract::getConfigService
     */
    public function testGetConfigService()
    {
        $reflectedClass = new \ReflectionClass($this->commandAbstract);
        $reflectedMethod = $reflectedClass->getMethod('getConfigService');
        $reflectedMethod->setAccessible(true);

        $logger = $reflectedMethod->invoke($this->commandAbstract);

        $this->assertTrue($logger instanceof ConfigService);
    }

    /**
     * Test the Get Logger Service.
     *
     * @return void
     * @covers \Reliv\Deploy\Command\CommandAbstract::getLoggerService
     */
    public function testGetLoggerService()
    {
        $reflectedClass = new \ReflectionClass($this->commandAbstract);
        $reflectedMethod = $reflectedClass->getMethod('getLoggerService');
        $reflectedMethod->setAccessible(true);

        $logger = $reflectedMethod->invoke($this->commandAbstract);

        $this->assertTrue($logger instanceof LoggerFactory);
    }
}
