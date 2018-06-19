<?php
/**
 * @file
 * This file provides classes which afford access to the directives contained
 * within the application's .ini file.
 *
 *
 * @class Settings
 * This class provides a means to load the .ini directives, which is necessary
 * to access them via this class.
 *
 * The class also provides the mechanism through which the scope of those 
 * directives are enforced. That is, directive groups can have their "private"
 * directive set to 1, which serves to restrict access to those directives.
 *
 * @method Settings::getDirective()
 * This method may be invoked in order to access private directives.
 *
 *
 * @class PublicSettings
 * This class extends the Settings class. By instantiating it, one may use the
 * "magic" __get and __isset methods to access the public directives. Using an 
 * instance of this class helps to ensure that private directives will not be 
 * leaked to visitors of the site.
 *
 */
abstract class Settings
{
	static private $privateDirectives = [];
	static private $publicDirectives = [];

	/**
	 * @method Settings::getDirective()
	 * Access a directive from a specified section.
	 *
	 * @param str $section
	 * A string representation of the name of the section, matching that of the
	 * corresponding section label in the .ini.
	 *
	 * @param str $keys
	 * One $key parameter can be passed to access a standalone directive in the
	 * .ini file.
	 * When accessing an array of directives, specify the array with the first
	 * $key parameter. Then, to access a specific directive within that array, 
	 * pass an additional $key parameter which matches the desired array key.
	 */
	public static function getDirective($section, ...$keys) {

		if (empty($keys)) {
			throw new Exception(__METHOD__.' expects at least one key.');
		}

		$sectionDirs	=	self::extractSection($section);

		foreach ($keys as $key) {

			$value 		=	self::getDirectiveValue($sectionDirs, $key);
			if (is_array($value)) $sectionDirs = $value;
			else break;
		}

		return $value;
	}

	/**
	 * @method Settings::load()
	 * Load a configuration file for access via this class.
	 *
	 * @param str $ini
	 * The filename of the configuration (.ini) file.
	 *
	 * @param bool $processSections
	 * From the manual (php.net):
	 * [By settings the process_sections parameter to TRUE, you get a multi-
	 * dimensional array, with the section names and settings included.]
	 * Here, the default is TRUE.
	 */
	public static function load($ini, $processSections = TRUE) {

		$settings	=	self::parseIni($ini, $processSections);

		foreach ($settings as $sectionName => $array) {

			$isPrivate	=	self::sectionIsPrivate($array);
			$dirs		=	self::unpackSettings($array);
			self::addDirectivesToSection($sectionName, $dirs, $isPrivate);
		}
	}

	/**
	 * @method Settings::unload()
	 * Unload all configuration from the class.
	 */
	public static function unload()
	{
		self::$privateDirectives = [];
		self::$publicDirectives = [];
	}

	/**
	 * @method __get()
	 * Access a public directive from a child class.
	 *
	 * @param str $key
	 * The name of the public directive. To access a section within the .ini,
	 * preface the directive name with the section name, followed immediately
	 * by a colon. This method supports all multi-dimensional arrays.
	 * E.g., db_admin:hostname
	 */
	public function __get($keys) {

		$keys	=	$this->parseKeysArg($keys);
		$dirSet	=	self::$publicDirectives;
		foreach ($keys as $key) {
			if (is_array($dirSet[$key])) {
				$dirSet	=	$dirSet[$key];
			}
			else {
				$value	=	$dirSet[$key];
				break;
			}
		}
		if (!isset($value)) {
			throw new Exception(__METHOD__ . ': No public directive found.');
		}
		return $value;
	}

	/**
	 * @method __isset()
	 * Check whether a public directive is accessible.
	 *
	 * @param str $key
	 * The name of the public directive. To access a section within the .ini,
	 * preface the directive name with the section name, followed immediately
	 * by a colon. This method supports all multi-dimensional arrays.
	 * E.g., db_admin:hostname
	 */
	public function __isset($key) {

		$keys	=	$this->parseKeysArg($keys);
		$dirSet	=	self::$publicDirectives;
		foreach ($keys as $key) {
			if (isset($dirSet[$key]) && is_array($dirSet[$key])) {
				$dirSet	=	$dirSet[$key];
			}
			else {
				return isset($dirSet[$key]);
			}
		}
		return true; // Loop stopped on section or directive array.
	}

	private static function addDirectivesToSection($sectName, $dirs, $private = FALSE) {

		if ($private)	$dir = &self::$privateDirectives;
		else			$dir = &self::$publicDirectives;
		$dir[$sectName]	=	$dirs;
	}

	private static function extractSection($section) {

		if (isset(self::$privateDirectives[$section])) {
			$sectionDirs = self::$privateDirectives[$section];
		}
		elseif (isset(self::$publicDirectives[$section])) {
			$sectionDirs = self::$publicDirectives[$section];
		}
		else {
			throw new Exception(__METHOD__.': Section '.$section.' not found.');
		}
		return $sectionDirs;
	}

	private static function getDirectiveValue($dirs, $key) {

		$value	=	(isset($dirs[$key])) ? $dirs[$key] : null;
		if ($value === null) {

			$message	=	__METHOD__ . ": $key not found.";
			$message	.=	' NULL values are invalid -- simply omit the directive.';
			throw new Exception($message);
		}
		return $value;
	}

	private static function parseIni($ini, $processSections) {

		$settings = parse_ini_file($ini, $processSections);

		if ($settings === false) {

			throw new Exception(
				__METHOD__ . ': Could not parse configuration file specified: "' . $ini . '"'
			);
		}

		return $settings;
	}

	private function parseKeysArg($keys) {

		return explode(':', $keys);
	}

	private static function sectionIsPrivate($settings) {

		return (
			array_key_exists('private', $settings)
			&& $settings['private'] === "1"
		) ? true : false;
	}

	/**
	 * @method Settings::unpackSettings()
	 * Unpack an array of directives into a multidimensional array.
	 */
	private static function unpackSettings($array)
	{
		$settings = [];

		foreach ($array as $directive => $setting) {

			if (is_array($setting)) {

				$settings[$directive] = self::unpackSettings($directive, $setting);
			}
			else {

				$settings[$directive] = $setting;
			}
		}

		return $settings;
	}
}

class PublicSettings extends Settings {}

