<?php
/**
 * @file
 * This file provides a dependency-injection container intended to be used to
 * package the objects required to construct a Router.
 *
 */
class RoutingDIContainer {

	public $request;
	public $db;
	public $logger;
	public $user;

	/**
	 * @method RoutingDIContainer::__construct()
	 * Construct a Dependency-Injection container for a Router.
	 *
	 * @param Request $req
	 * An instance of the Request class, which represents the initial request
	 * sent by the client.
	 *
	 * @param Db $db
	 * An instance of the Db class, which represents a connection to a database.
	 *
	 * @param Logger $logger
	 * An instance of the Logger class, which is responsible for persisting
	 * errors and other messages to the database and log file(s).
	 *
	 * @param UserManager $user
	 * An instance of the UserManager class, which represents the currently
	 * logged-in user.
	 *
	 * @return RoutingDIContainer
	 */
	public function __construct(Request $req, Db $db, Logger $logger, UserManager $user) {
	
		$this->request	=	$req;
		$this->db		=	$db;
		$this->logger	=	$logger;
		$this->user		=	$user;
	}
}

