<?php
/**
 * @file ConfigTest.php
 * This file provides unit tests for the Config class.
 *
 */
use PHPUnit\Framework\TestCase;
use JackBradford\ActionRouter\Config\Config;

final class ConfigTest extends TestCase {

    private $config;

    protected function setUp() {

        $file = '/var/www/vhosts/action-router/src/Config/actionrouter.conf.json';
        $this->config = new Config();
        $this->config->load($file);
    }

    protected function tearDown() {

        $this->config = null;
    }

    public function testCannotLoadFileWithInvalidProperty() {

        $this->expectException(Exception::class);
  
        $file = '/var/www/vhosts/action-router/tests/Config/test.conf.json';
        $config = new Config();
        $config->load($file);
    }

    public function testCanUnloadSettings() {

        $this->expectException(Exception::class);
    }
}

