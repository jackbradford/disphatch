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
 * Command Line Usage
 * ==================
 * If invoking the system via the command line, this file parses the
 * arguments given as part of the command (if applicable) and stores them
 * in the $_GET array.
 *
 *
 * User Data - Cookies
 * ===================
 * Cookies are checked and set to their defaults if necessary.
 *
 *
 * User Management - Sentinel by Cartalyst
 * =======================================
 * Sentinel, the user management library by Cartalyst is loaded.
 *
 *
 * Required Resources:
 * ===================
 * Before loading any classes, global_functions.php and the Settings class are
 * required. The ale_im.ini file is also required and loaded by the Settings class.
 *
 */

define('LOGS_PATH', '../src/log');
define('APP_ERRORS', LOGS_PATH.'/app-errors.log');

ini_set('error_reporting', E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', APP_ERRORS);
ini_set('display_errors', 0);
ini_set('date.timezone', 'America/New_York');

const CONTROLLERS 			=	[

	'public'			=>	'PublicController',
	'admin'				=>	'AdminController',
	'qb'				=>	'QbController',
	'record_request'	=>	'RecordRequestController',
];

const ADMIN					=	'admin';
const ADMIN_PATH			=	'../src/mod/admin';
const ADMIN_TEMPLATE		=	'../src/view/layout_aldb.php';
const AL_DB					=	'al_db';
const ALE_QB				=	'ale_qb';
const CONFIG_PATH			=	'../src/config';
const DATABASE				=	'al_db';
const DEFAULT_CONTROLLER	=	'admin';
const GUEST					=	'guest';
const INC_PATH				=	'../src/inc';
const INI					=	'../src/config/ale_im.ini';
const LIB_PATH				=	'../src/lib';
const NOV_DB				=	'nov_qb';
const NOV_QB				=	'nov_qb';
const PAGE_PATH				=	'../src/mod/public/view/pages';
const PUBLIC_PATH			=	'../src/mod/public';
const PUBLIC_TEMPLATE		=	'../src/view/layout.php';
const RESOURCES_PATH		=	'../src';
const SENTINEL_PATH			=	'../src/lib/vendor/cartalyst/sentinel';
const STORE_PATH			=	'../src/lib/store';
const TEMPLATE_PATH			=	'../src/view';


require_once '../src/lib/global_functions.php';
startNewOutputBuffer();
parseArgvIntoGet();
loadSettings();

if (!inTestMode()) {

	loadRequiredClasses();
	loadUserPreferencesFromCookies();
}

loadUserManagementSystem();

