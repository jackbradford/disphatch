<?php
/**
 * @file
 * This file provides the Settings class, which affords access to the
 * directives contained within a configuration file (e.g. the application's
 * .conf.json file).
 *
 * @class Settings
 * This class provides a means to load JSON config file directives, which is
 * necessary to access them via this class. The class does not allow the
 * directives to be changed once they are loaded.
 *
 */
namespace JackBradford\ActionRouter\Config;

class Settings {

    private $action_query_str;
    private $async_post_flag;
    private $dbs;
    private $controllers;
    private $ctrl_query_str;
    private $default_action;
    private $templates;
    private $users;

    /**
     * @method Settings::__construct
     *
     * @return Settings
     */
    public function __construct() {}

    /**
     * @method Settings::getDirective()
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

            $message = __METHOD__.': Invalid property: ';
            $message .= $sectionName;
            throw new Exception($message);
        }

        return $this->{$section};
    }

    /**
     * @method Settings::load
     * Load a configuration file for access via this class.
     *
     * @param str $file
     * The filename of the configuration (.conf.json) file.
     *
     * @return void
     */
    public function load($file) {

        $settings = json_decode(file_get_contents($file));

        foreach ($settings as $sectionName => $section) {

            if (!property_exists($this, $sectionName)) {

                $message = __METHOD__.': Invalid property: ';
                $message .= $sectionName;
                throw new Exception($message);
            }

            $this->{$sectionName} = $section;
        }
    }

    /**
     * @method Settings::unload
     * Unload all configuration from the class.
     *
     * @return void
     */
    public function unload() {

        $properties = get_object_vars($this);
        foreach ($properties as $prop => $val) unset($this->{$prop});
    }
}

