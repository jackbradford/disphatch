<?php
/**
 * @file
 * This file provides the router.
 *
 * The router loads a list of "routes," which map actions to their
 * corresponding controllers. A route is specified with the request. This
 * class has the responsibility of routing the request to the appropriate
 * controller, which oversees the business logic of the requested task and
 * returns a response to the router.
 *
 */
namespace JackBradford\ActionRouter\Router;

use JackBradford\ActionRouter\Config\Settings;
use JackBradford\ActionRouter\Config\Config;
use JackBradford\ActionRouter\Db\Db;
use JackBradford\ActionRouter\Db\DbFactory;
use JackBradford\ActionRouter\Etc\Request;
use JackBradford\ActionRouter\Etc\UserManager;
use JackBradford\ActionRouter\Etc\Logger;
use JackBradford\ActionRouter\Etc\NotLoggedInException;
use JackBradford\ActionRouter\Etc\RoutingDIContainer;

/*
 * use JackBradford\ActionRouter\Config;
 * use JackBradford\ActionRouter\Db;
 * use JackBradford\ActionRouter\Etc;
 */

class Router extends Output {

	protected	$user;
	private		$controller;
	private		$action;
	private		$db;
	private		$request;
	private		$logger;
	private		$dc;
	private		$serveContentOnly	=	false;
	private		$routes				=	array();

	/**
	 * @method Router::__construct()
	 * Construct a new instance of the Router class, which is responsible for
	 * inspecting the request, identifying and validating the requested action,
	 * calling the appropriate controller method, and returning the result in
	 * the requested format.
	 *
	 * @see Router::init
	 * This class is instantiated via the Factory method. To create an instance,
	 * use Router::init().
	 *
	 * @param RoutingDIContainer $dc
	 * An instance of the Routing Dependency Injection Container, which contains
	 * a Request instance, Logger instance, Db Abstraction instance, and a User Management
	 * instance.
	 */
	private function __construct(RoutingDIContainer $dc) {

		try {
	
			$this->dc		=	$dc;
			$this->request	=	$dc->request;
			$this->logger	=	$dc->logger;
			$this->db		=	$dc->db;
			$this->user		=	$dc->user;
			$this->routes 	=	$this->loadRoutes();
		}
		catch (Exception $e) {

			if (isset($dc->logger) && ($dc->logger instanceof Logger)) {

				$dc->logger->logError($e);
			}
			$m	=	'Could not construct Router.';
			throw new Exception($m, 0, $e);
		}
	}

	/**
	 * @method Router::init()
	 * Initialize the Router before handling the current request.
	 *
	 * @param str $configPath
	 * The path to the configuration file.
	 *
	 * @return Router
	 */
	public static function init($iniPath) {

		try {

			Config::setConfigurationFromFile($iniPath);
			$request	=	new Request();
			$settings	=	new PublicSettings();
			$user		=	new UserManager($request, $settings);

			if (!$user->isAuthorizedToMakeRequest()) {

				$message	=	'User must log in; request was not for authorization. ';
				$message	.=	'Asking user to send authorization request...';
				throw new NotLoggedInException($message);
			}

			$db			=	DbFactory::getDbInst();
			$logger		=	new Logger($db);
			$dc			=	new RoutingDIContainer($request, $db, $logger, $user);

			return new Router($dc);

		} catch (NotLoggedInException $e) {

			if ($request->isFromCLI()) UserManager::askForCLILogin();
			else {
			
				$login = ($request->isAsync()) ? 'askForAsyncLogin' : 'sendToLoginPage';
				UserManager::{ $login }();
			}

		} catch (Exception $e) {

			$m	=	$e->getMessage() . ' ' . $e->getTraceAsString();
			error_log('ActionRouter failed to initialize: '.$m);
		}
	}

	/**
	 * @method Router::routeAndExecuteRequest()
	 * Route the request to the appropriate controller action, then execute
	 * that action.
	 *
	 * @param bool $serveClientAppOnSync
	 * Specify whether the router should serve the HTML necessary to run the
	 * client application, rather than the requested data, if the request is
	 * synchronous.
	 *
	 * @return void
	 * Emits a JSON object (or the HTML necessary to run the client 
	 * application).
	 */
	public function routeAndExecuteRequest($serveClientAppOnSync=true) {

		try {

			$ctrlModel	=	CONTROLLERS[$this->request->getNameOfRequestedController()];
			$controller	=	new $ctrlModel($this->router, $this->dc);

			$this->setController($controller);
			$this->setControllerAction($this->request->getNameOfRequestedAction());

			$result	= (!$this->request->isAsync() && $serveClientAppOnSync)
				? $this->callClientApp()
				: $this->callRequestedAction();

			$this->setContent($result);
			$this->serveContent();

		} catch (Exception $e) {

			$this->logger->logError($e);
			$this->serveErrorNotice($e);
		}
	}

	/**
	 * @method Router::callClientApp()
	 * In some instances, depending on client configuration, it may be
	 * advantageous to serve the client app itself rather than the data
	 * requested. Via such configuration, the same URL can be used both to 
	 * resolve the initial request (by serving the client app only) and 
	 * to deliver the data necessary in that context when called subsequently
	 * by that delivered client.
	 *
	 * @return void
	 */
	public function callClientApp() {

		switch ($this->request->getNameOfRequestedController()) {

			case 'admin':
			case 'record_request':
				$this->setTemplate(ADMIN_TEMPLATE);
				break;

			default:
				$this->setTemplate(PUBLIC_TEMPLATE);
		}

		ob_start();
		// TODO: should this be configurable?
		require_once TEMPLATE_PATH . '/admin_client.php';
		return ob_get_clean();
	}

	/**
	 * @method callRequestedAction()
	 * Call the action specified in the request via the appropriate controller.
	 *
	 * @return string
	 * Returns the contents returned by the controller method specified in the 
	 * request.
	 *
	 * @important
	 * The user-defined controller method should NOT emit its response. Any
	 * plain-text/HTML should be captured via (e.g.) the output buffer and
	 * returned to this method.
	 */
	public function callRequestedAction() {

		if (isset($this->action)) $action = $this->action;
		else throw new Exception(__METHOD__.': No Action Given.', 302);

		$content	=	$this->controller->{ $action }();

		if ($content === false) {

			$message = __METHOD__ . ': User-Defined controller returned FALSE.';
			throw new Exception($message, 306);
		}

		return $content;
	}
	
	/**
	 * @method Router::getActionName()
	 * Get the name of the currently-set action which will/has been run by the
	 * controller.
	 *
	 * @return str
	 * Returns the name of the action.
	 */
	public function getActionName() {

		return $this->action;
	}
	
	/**
	 * @method Router::getControllerName()
	 * Get the name of the currently-set controller which will/has been used to
	 * handle the request.
	 *
	 * @return str
	 * Returns the result of the controller instance's toString() method.
	 */
	public function getControllerName() {

		return (string) $this->controller;
	}

	/**
	 * @method Router::serveContent()
	 * Serve the content set in the $content property.
	 *
	 * @return void
	 * In the case of an asynchronous request, or in the case the $serveContentOnly
	 * property is set to TRUE, the content contained in the $content property
	 * will be flushed to the client.
	 *
	 * Otherwise, the template file set in the
	 * $templatePath property will be sent to the client. That template file should
	 * access the $content property to display the content within the template.
	 */
	public function serveContent() {

		if 		($this->request->isAsync()) 		$this->flushContent();
		elseif	($this->serveContentOnly === true)	$this->flushContent();
		else	$this->flushAll();
	}

	/**
	 * @method serveErrorNotice()
	 * Serve the Error Page (or deliver an error notice to the client) in the
	 * event of an unrecoverable (but non-fatal) exception.
	 *
	 * @param Exception $e
	 * The exception which could not be recovered from.
	 */
	public function serveErrorNotice($e) {

		ob_start();

		if ($this->request->isAsync()) {

			$res	=	new AsyncResponse([
					'success'	=>	false,
					'title'		=>	'The server could not process your request.',
					'message'	=>	$e->getMessage(),
			]);
			$res->sendResponse();

		} else {

			switch ($this->request->getNameOfRequestedController()) {

				case 'admin':
					$this->setTemplate(ADMIN_TEMPLATE);
					break;

				default:
					$this->setTemplate(PUBLIC_TEMPLATE);
			}
			require_once PUBLIC_PATH . '/view/pages/error.php';
		}

		$this->setContent(ob_get_clean());
		$this->serveContent();
	}

	/**
	 * @method Router::setController()
	 * Specifiy the controller which should handle the request.
	 *
	 * @param IRequestController $controller
	 * The instance of the controller.
	 * 
	 * @return void
	 */
	public function setController(IRequestController $controller) {

		if (array_key_exists((string) $controller, $this->routes)) {

			$this->controller	=	$controller;

		} else {

			$m = __METHOD__ . ': Invalid controller.';
			throw new Exception($m, 304);
		}
	}
	
	/**
	 * @method Router::setControllerAction()
	 * Specify the action to be executed by the controller.
	 *
	 * @param str $action
	 * The name of the action, as defined by its method name.
	 *
	 * @return void
	 */
	public function setControllerAction($action) {

		if (in_array($action, $this->routes[(string) $this->controller])) {

			$this->action = $action;

		} else {

			$message = __METHOD__ . ': Action not valid for selected controller';
			throw new Exception($message, 305);
		}
		
	}
	
	/**
	 * @method Router::toggleServeContentOnly()
	 * Toggle or specify the setting which determines whether content should be
	 * sent to the client within an HTML template or by itself, e.g. in the case
	 * of the transmission of JSON data.
	 *
	 * @param bool $setting
	 * The desired setting. Defaults to null, which toggles the current setting.
	 */
	public function toggleServeContentOnly($setting = null) {

		if ($setting === null) {

			$this->serveContentOnly = ($this->serveContentOnly === false)
				? true
				: false;
		}
		else {

			$this->serveContentOnly = ($setting) ? true : false;
		}
	}
	
	/**
	 * @method loadRoutes()
	 * Load the list of routes from the database.
	 * TODO: Having the routes in the database prevents them from being
	 * included in the version control. Perhaps re-implement with a config file.
	 *
	 * @return array
	 * Returns an associative array of the routes, arranged via the actions'
	 * controllers.
	 */
	private function loadRoutes() {

		$routes		=	[];
		$actionFlds	=	[new QueryField('action')];
		$ctrlFlds	=	[new QueryField('controller')];

		$results	=	$this->db->select('actions')
			->fields([
				'table'	=>	'actions', 
				'fields'=>	$actionFlds,
			])
			->fields([
				'table'	=>	'controllers', 
				'fields'=>	$ctrlFlds,
			])
			->joinTable('left', 'controllers', 'actions.controller', '=', 'controllers.id')
			->execute()
			->fetchAllResults();

		for ($j=0 ; $j<count($results) ; $j++) {

			$ctrl				=	$results[$j]['controller'];
			$routes[$ctrl][]	=	$results[$j]['action'];
		}
		return $routes;
	}
}

