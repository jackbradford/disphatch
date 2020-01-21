<?php
/**
 * @file ConfigTest.php
 * This file provides unit tests for the Config class.
 *
 */
use PHPUnit\Framework\TestCase;
use JackBradford\Disphatch\Config\Config;

final class ConfigTest extends TestCase {

    private $config;

    protected function setUp() {

        $file = '/var/www/vhosts/action-router/src/Config/disphatch.conf.json';
        $this->config = new Config();
        $this->config->load($file);
    }

    protected function tearDown() {

        $this->config = null;
    }

    /**
     * @method  testCannotLoadFileWithInvalidProperty
     * This method tests the method Config::load() to ensure an invalid config
     * file cannot be loaded. The expected behavior is that the method throws
     * an exception.
     */
    public function testCannotLoadFileWithInvalidProperty() {

        $this->expectException(Exception::class);

        $file = '/var/www/vhosts/action-router/tests/Config/test.conf.json';
        $config = new Config();
        $config->load($file);
    }

    /**
     * @method testCanUnloadSettings
     * Test the functioning of Config::unload with a subsequent call to
     * Config::getDirective. The directive should be unset and result in an
     * exception.
     */
    public function testCanUnloadSettings() {

        $this->expectException(Exception::class);
        $this->config->unload();
        $dbs = $this->config->getDirective('dbs');
    }

    /**
     * @method testCanGetDirective
     * Test the Config::getDirective method to ensure directives can be
     * retrieved.
     */
    public function testCanGetDirective() {

        $testDbHost = $this->config->getDirective('dbs')->test->hostname;
        $this->assertEquals('localhost', $testDbHost);
    }

    /**
     * @method testCannotGetInvalidDirective
     * Test the Config::getDirective method to ensute an exception is thrown
     * when attempting to access an undefined/invalid directive.
     */
    public function testCannotGetInvalidDirective() {

        $this->expectException(InvalidArgumentException::class);
        $this->config->getDirective('abs');
    }
}

