<?php
/**
 * @file IRequestController.php
 * Provides an interface for the request controllers.
 *
 */
namespace JackBradford\ActionRouter\Controllers;

use JackBradford\ActionRouter\Etc\RoutingDIContainer;
use JackBradford\ActionRouter\Router\Router;

interface IRequestController {

    public function __construct(Router $router, RoutingDIContainer $dc);
    public function __toString();
}

