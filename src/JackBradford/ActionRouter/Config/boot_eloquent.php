<?php
/**
 * @file
 * This file sets up a new Eloquent Capsule instance, which is necessary to
 * enable use of Sentinel, a user management system by Cartalyst.
 *
 * This file is used to integrate Sentinel with the application. Once the file
 * has been included, Sentinel's methods should be available globally.
 *
 */
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Database\Capsule\Manager as Capsule;

// Include the composer autoload file.
require LIB_PATH . '/vendor/autoload.php';

$capsule	=	new Capsule;
$capsule->addConnection([
			'driver'	=>	'mysql',
			'host'		=>	Settings::getDirective('db_admin', 'hostname'),
			'database'	=>	Settings::getDirective('db_admin', 'database'),
			'username'	=>	Settings::getDirective('db_admin', 'username'),
			'password'	=>	Settings::getDirective('db_admin', 'password'),
			'charset'	=>	'utf8',
			'collation'	=>	'utf8_unicode_ci',
]);
$capsule->bootEloquent();

