<?php
/**
 * @file
 * This file provides factories for producing instances of the database-
 * access abstraction layer.
 *
 * @class DbFactory
 * This class provides the aforementioned factories. It depends on its parent,
 * the Settings class, and its methods return instances of the database-access 
 * abstraction class.
 *
 */
class DbFactory extends Settings
{
	public static function getDbInst() {

		$qp		=	new MySqlQueryPreparer();
		$host	=	parent::getDirective('db', 'hostname');
		$pass	=	parent::getDirective('db', 'password');
		$db		=	parent::getDirective('db', 'database');
		$user	=	parent::getDirective('db', 'username');
		return new Db($host, $pass, $db, $user, $qp);
	}
}

