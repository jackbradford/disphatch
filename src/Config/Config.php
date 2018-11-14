<?php
/**
 * @file
 * This file provides definitions of constants and loads up the classes
 * required by the system. 
 *
 *
 * Output Buffering
 * ================
 * The output buffer is initialized at the beginning of the script to increase
 * the degree of control over the final output to the browser.
 *
 *
 * User Management - Sentinel by Cartalyst
 * =======================================
 * Sentinel, the user management library by Cartalyst is loaded.
 *
 *
 */
namespace JackBradford\ActionRouter\Config;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Capsule\Manager as Capsule;

class Config {

    private $action_query_str;
    private $async_post_flag;
    private $dbs;
    private $controllers;
    private $ctrl_query_str;
    private $default_action;
    private $login_page_path;
    private $permissions;
    private $templates;
    private $users;

    /**
     * @method Config::__construct
     * Create a new Config instance.
     *
     * @return Config
     *
     */
    public function __construct() {}

    /**
     * @method Config::getDirective()
     * Access a directive from a specified section.
     *
     * @param str $section
     * The name of the section to access.
     *
     * @return mixed
     * Returns the value (or array) of the specified directive.
     *
     */
    public static function getDirective($section) {

        if (!property_exists($this, $section)) {

            $message = __METHOD__.': Invalid property: '.$section;
            throw new InvalidArgumentException($message);
        }

        if (!isset($this->{$section})) {

            throw new InvalidArgumentException('Directive is not set.');
        }

        return $this->{$section};
    }

    /**
     * @method Config::load
     * Load a configuration file for access via this class.
     *
     * @param str $file
     * The filename of the configuration (.conf.json) file.
     *
     * @return void
     */
    public function load($file) {

        $settings = $this->validateSettings(
            json_decode(file_get_contents($file))
        );

        foreach ($settings as $sectionName => $section) {

            if (!property_exists($this, $sectionName)) {

                $message = __METHOD__.': Invalid property: '.$sectionName;
                throw new Exception($message);
            }

            $this->{$sectionName} = $section;
        }
    }

    /**
     * @method Config::setConfigurationFromFile
     * Configure the router by loading directives from the .ini file.
     *
     * @param str $file
     * The filename of the configuration (.conf.json) file.
     *
     * @return void
     *
     */
    public function setConfigurationFromFile($file) {

        $this->load($file);
        $this->loadUserManagementSystem();
        $this->defineGlobalConstants();
    }

    /**
     * @method Config::unload
     * Unload all configuration from the class.
     *
     * @return void
     */
    public function unload() {

        $properties = get_object_vars($this);
        foreach ($properties as $prop => $val) unset($this->{$prop});
    }

    /**
     * @method Config::defineGlobalConstants
     * Extract settings from the configuration file and use them to set global
     * constants.
     *
     * @return void
     *
     */
    private function defineGlobalConstants() {

        $controllers = $this->getDirective('controllers');
        $defaultCtrl = $controllers->default;
        unset($controllers->default);

        define('CONTROLLERS', (array) $controllers);
        define('TEMPLATES', (array) $this->getDirective('templates'));
        define('DEFAULT_CONTROLLER', $defaultCtrl);
        define('DEFAULT_ACTION', $this->getDirective('default_action'));
        define('CTRL_QUERY_STR', $this->getDirective('ctrl_query_str'));
        define('ACTION_QUERY_STR', $this->getDirective('action_query_str'));
        define('ASYNC_POST_FLAG', $this->getDirective('async_post_flag'));
    }

    /**
     * @method Config::loadUserManagementSystem
     * Load Sentinel, the user management system.
     *
     * @return void
     *
     */
    private function loadUserManagementSystem() {

        $db = $this->getDirective('dbs')->sentinel_db;
        $dbConf = $this->getDirective('dbs')->{$db};
        $capsule = new Capsule;

        $capsule->addConnection([

            'driver' => 'mysql',
            'host' => $dbConf->hostname,
            'database' => $dbConf->database,
            'username' => $dbConf->username,
            'password' => $dbConf->password,
            'charset' => 'utf8',
            'collation'=> 'utf8_unicode_ci',
        ]);

        $capsule->bootEloquent();
    }

    /**
     * @method Config::parseArgvIntoGet
     * Translate request parameters given via the CLI into the $_GET superglobal
     * array. This method expects the parameters to be in the form 'param=value',
     * with each parameter separated by a space. This method serves to maintain
     * the $_GET interface while using the application via the CLI.
     *
     * @return void
     */
    public function parseArgvIntoGet() {

        global $argv;
        if (!isset($argv)) return;

        foreach ($argv as $arg) {

            $e = explode("=", $arg);

            if (count($e) == 2) {

                $_GET[$e[0]] = $e[1];
            }
            else {

                $_GET[$e[0]] = 0;
            }
        }
    }

    /**
     * @method Config::validateSettings
     * Check whether the settings are in the expected format.
     *
     * @param obj $settings
     * The settings should be in stdClass format.
     *
     * @return obj
     * The method will return the settings given if they are valid. Otherwise,
     * the method will throw an exception.
     */
    private function validateSettings($settings) {

        if (!is_object($settings)) {

            $m = __METHOD__ . ': Expects object. Check file location and 
            verify JSON is valid.';
            throw new Exception($m);
        }

        return $settings;
    }
}

