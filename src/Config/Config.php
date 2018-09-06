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

class Config {

	/**
	 * @method Config::setConfigurationFromFile
	 * Configure the router by loading directives from the .ini file.
	 *
	 * @param str $iniPath
	 * The path of the configuration file.
	 *
	 * @return void
	 *
	 */
	public static function setConfigurationFromFile($iniPath) {

		Settings::load($iniPath);
		$this->loadUserManagementSystem();
		$this->defineGlobalConstants();
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

		define('CONTROLLERS',		Settings::getDirective('controllers', 'ctrl'));
		define('TEMPLATES',			Settings::getDirective('templates', 'tmp'));
		define('DEFAULT_CONTROLLER',Settings::getDirective('controllers', 'default'));
	}

	/**
	 * @method Config::loadUserManagementSystem
	 * Load Sentinel, the user management system.
	 *
	 * @return void
	 *
	 */
	private function loadUserManagementSystem() {

		require_once dirname(__FILE__) . '/integrate_sentinel.php';
	}
}

