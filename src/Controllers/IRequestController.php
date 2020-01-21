<?php
/**
 * @file IRequestController.php
 * Provides an interface for the request controllers.
 *
 */
namespace JackBradford\Disphatch\Controllers;

use JackBradford\Disphatch\Etc\RoutingDIContainer;
use JackBradford\Disphatch\Router\Router;

interface IRequestController {

    public function __construct(Router $router, RoutingDIContainer $dc);
    public function __toString();
}

