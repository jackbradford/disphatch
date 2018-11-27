<?php
/**
 * @file
 * This file provides a dependency-injection container intended to be used to
 * package the objects required to construct a Router.
 *
 */
namespace JackBradford\ActionRouter\Etc;

use JackBradford\ActionRouter\Config\Config;

class RoutingDIContainer {

    public $config;
    public $db;
    public $logger;
    public $request;
    public $user;

    /**
     * @method RoutingDIContainer::__construct()
     * Construct a Dependency-Injection container for a Router.
     *
     * @param Config $config
     * An instance of the Config class, which represents the router's
     * configuration.
     *
     * @param Request $req
     * An instance of the Request class, which represents the initial request
     * sent by the client.
     *
     * @param UserManager $user
     * An instance of the UserManager class, which represents the currently
     * logged-in user.
     *
     * @param Object $db
     * An instance of a Database abstraction class, which represents a
     * connection to a database. This is optional, and can be used to supply
     * the request-handling controllers with a database connection such that
     * it's not necessary to create one in each method or in each controller.
     *
     * @return RoutingDIContainer
     */
    public function __construct(
        Config $config,
        Request $req,
        ILogger $logger,
        UserManager $user,
        $db = null
    ) {

        $this->config = $config;
        $this->request = $req;
        $this->logger = $logger;
        $this->user = $user;
        if ($this->validateDb($db)) $this->db = $db;
        else {
            $message = 'Expects an object for argument $db.';
            throw new \InvalidArgumentException($message);
        }
    }

    private function validateDb($db) {

        return (is_object($db) || is_null($db)) ? true : false;
    }
}

