<?php
/**
 * @file Controllers/Controller.php
 * This file provides an abstract class which serves to standardize the
 * implementation of controllers for use with the router.
 *
 */
namespace JackBradford\Disphatch\Controllers;

use JackBradford\Disphatch\Etc\RoutingDIContainer;
use JackBradford\Disphatch\Router\Router;

abstract class Controller {

    protected $config;
    protected $db;
    protected $logger;
    protected $request;
    protected $response;
    protected $router;
    protected $userMgr;

    final public function __construct(Router $router, RoutingDIContainer $dc) {

        $this->router = $router;
        $this->config = $dc->config;
        $this->request = $dc->request;
        $this->logger = $dc->logger;
        $this->db = $dc->db;
        $this->userMgr = $dc->user;
    }

    final public function __toString() {

        return get_class($this);
    }

    /**
     * @method Controller::fromGET
     * Access a parameter passed to the script via $_GET and return NULL if
     * the index is not defined. This has the advantage of not throwing a
     * notice in the error log.
     *
     * @param str $index
     * The index of the $_GET parameter.
     *
     * @return mixed
     * Returns the value of $_GET[$index] or NULL if that index is not defined.
     */
    public function fromGET($index) {

        return (isset($_GET[$index])) ? $_GET[$index] : null;
    }

    /**
     * @method Controller::setResponse
     * Commit the result(s) of the action and provide content and/or data with
     * which to reply to the originator of the request.
     *
     * @param ControllerResponse $response
     * An instance of the ControllerResponse class.
     *
     * @return void
     */
    final public function setResponse(ControllerResponse $response) {

        $this->response = $response;
    }
}

