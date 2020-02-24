<?php
/**
 * @file
 * This file provides a means to load configuration settings from a file
 * and retreive them without risk of altering the directives. It also serves
 * to initialize the system, including the loading of the user management
 * system.
 *
 */
namespace JackBradford\Disphatch\Config;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Capsule\Manager as Capsule;

class Config {

    private $action_query_str_label;
    private $ctrl_query_str_label;
    private $async_post_flag;
    private $default_action;
    private $dev;
    private $email;
    private $client_apps;
    private $controllers;
    private $dbs;
    private $login_page_path;
    private $permissions;
    private $roles;
    private $templates;
    private $users;

    /**
     * @method Config::__construct
     * Create a new Config instance.
     *
     * @return Config
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
     */
    public function getDirective(string $section) {

        if (!property_exists($this, $section)) {

            throw new \InvalidArgumentException(
                'Invalid property: ' . $section . '.'
            );
        }

        if (!isset($this->{$section})) {

            throw new \InvalidArgumentException(
                'Directive is not set: ' . $section . '.'
            );
        }

        return $this->{$section};
    }

    /**
     * @method Config::load
     * Load a configuration file for access via this class. The configuration
     * file should be valid JSON. Each top-level JSON object will be accessible
     * via the properties of this class.
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

                throw new \Exception(
                    'Invalid property: ' . $sectionName . '.'
                );
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
     */
    public function setConfigurationFromFile($file) {

        $this->load($file);
        $this->loadUserManagementSystem();
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
     * @method Config::loadUserManagementSystem
     * Load Sentinel, the user management system. This method allows Sentinel
     * access to the database as specified in disphatch.conf.json.
     *
     * This method also creates the admin user if it doesn't yet exist.
     *
     * @return void
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
        $this->initRoles();
        $this->initAdminUser();
    }

    /**
     * @method Config::initAdminUser()
     * Initialize the admin user account if it does not already exist.
     *
     * @return void
     */
    private function initAdminUser() {

        if (null === Sentinel::findByCredentials(['login'=>'admin'])) {

            $admin = Sentinel::registerAndActivate([
                'email' => 'admin',
                'password' => 'admin',
                'first_name' => 'admin',
            ]);
            $adminRole = Sentinel::findRoleBySlug('administrator');
            $adminRole->users()->attach($admin);
        }
    }

    /**
     * @method Config::initRoles()
     * Create each role with the given permissions, as specified in
     * disphatch.conf.json.
     *
     * @return void
     */
    private function initRoles() {

        foreach ($this->roles as $slug => $permissions) {

            if (($role = Sentinel::findRoleBySlug($slug)) === null) {

                $role = Sentinel::getRoleRepository()->createModel()->create([
                    'name' => ucfirst($slug),
                    'slug' => $slug
                ]);
            }
            $permSet = [];
            foreach ($permissions as $perm) $permSet[$perm] = true;
            $role->permissions = $permSet;
            $role->save();
        }
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

            throw new \InvalidArgumentException(
                'Expected object. Check file location and verify JSON is valid.'
            );
        }

        return $settings;
    }
}

