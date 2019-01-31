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

use JackBradford\ActionRouter\Config\Config;
use JackBradford\ActionRouter\Controllers\ControllerResponse;
use JackBradford\ActionRouter\Controllers\IRequestController;
use JackBradford\ActionRouter\Etc\Request;
use JackBradford\ActionRouter\Etc\UserManager;
use JackBradford\ActionRouter\Etc\Logger;
use JackBradford\ActionRouter\Etc\Exceptions\NotLoggedInException;
use JackBradford\ActionRouter\Etc\RoutingDIContainer;

class Router extends Output {

    private $action;
    private $controller;
    private $db;
    private $dc;
    private $logger;
    private $request;
    private $routes = array();
    private $serveContentOnly = false;
    protected $user;

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
    public function __construct(RoutingDIContainer $dc) {

        try {

            $this->dc = $dc;
            $this->config = $dc->config;
            $this->request = $dc->request;
            $this->logger = $dc->logger;
            $this->db = $dc->db;
            $this->user = $dc->user;
        }
        catch (Exception $e) {

            $dc->logger->logError($e);
            $m = 'Could not construct Router. '.$e->getMessage();
            throw new \Exception($m, 0, $e);
        }
    }

    /**
     * @method Router::init()
     * Initialize the Router before handling the current request.
     *
     * @param str $configPath
     * The path to the configuration file.
     *
     * @param ILogger $logger
     * An instance of a logger class which implements the ILogger interface.
     *
     * @param object $db
     * An instance of a database-abstraction class. This will be supplied to
     * each request-handling controller.
     *
     * @return Router
     */
    public static function init($configPath, ILogger $logger=null, $db=null) {

        try {

            $config = new Config();
            $config->setConfigurationFromFile($configPath);
            $request = new Request($config);
            $user = new UserManager($request, $config);
            $logger = (is_null($logger)) ? new Logger() : $logger;

            return new Router(
                new RoutingDIContainer($config, $request, $logger, $user, $db)
            );
        }
        catch (Exception $e) {

            $m = $e->getMessage() . "\n" . $e->getTraceAsString();
            error_log('ActionRouter failed to initialize: '.$m);
        }
    }

    /**
     * @method Router::authorizeRequest()
     * Attempt to authorize the request by verifying that the end-user is
     * logged in, requesting a public resource, is using the CLI, or is
     * making an authentication request.
     *
     * @return void
     * Throws an exception if the request cannot be served without
     * authentication.
     */
    protected function authorizeRequest() {


        if (!$this->user->isAuthorizedToMakeRequest()) {

            throw new NotLoggedInException(
                'Authentication Required. User must log in before making '
                . 'this request.'
            );
        }
        if (!$this->user->authorizeAction(
            (string) $this->controller, $this->action)
        ) {

            throw new \Exception(
                'Insufficient Permission. User does not have the privileges '
                . 'necessary to perform the requested action.'
            );
        }

    }

    /**
     * @method Router::routeAndExecuteRequest()
     * Route the request to the appropriate controller action, then execute
     * that action.
     *
     * @param bool $serveClientAppOnSyncReq
     * Specify whether the router should serve the HTML necessary to run the
     * client application, rather than the requested data, if the request is
     * synchronous.
     *
     * @return void
     * Emits a JSON object (or the HTML necessary to run the client
     * application).
     */
    public function routeAndExecuteRequest($serveClientAppOnSyncReq=true) {

        try {

            $ctrlrName = $this->request->getClassNameOfRequestedController();
            $this->setController(new $ctrlrName($this, $this->dc));
            $this->setControllerAction($this->request->getNameOfRequestedAction());
            $this->authorizeRequest();

            $response = (!$this->request->isAsync() && $serveClientAppOnSyncReq)
                ? $this->callClientApp()
                : $this->callRequestedAction();

            $this->setResponse($response);
            $this->serveContent();
        }
        catch (NotLoggedInException $e) {

            self::requestLogin($this->request, $this->user);
        }
        catch (Exception $e) {

            $this->logger->logError($e);
            $this->serveErrorNotice($e);
        }
    }

    /**
     * @method Router::callClientApp()
     * In some instances, depending on configuration, it may be
     * advantageous to serve the client app itself rather than the data
     * requested. Via such configuration, the same URL can be used both to
     * resolve the initial request (by serving the client app only) and
     * to deliver the data necessary in that context when called subsequently
     * (and asynchronously) by that client app.
     *
     * @return str
     * Returns the contents of the HTML file of the appropriate app for
     * the requested controller, as configured by the user.
     */
    public function callClientApp() {

        $label = $this->request->getLabelOfRequestedController();
        $appPath = $this->config->getDirective('client_apps')->{$label};

        $this->toggleServeContentOnly(true);

        ob_start();
        require_once $appPath;
        $content = ob_get_clean();

        return new ControllerResponse(true, null, [], $content);
    }

    /**
     * @method Router::enableCLISession
     * To enable CLI sessions call this method before executing the request.
     * The method calls Router::holdCLISession, which will recursively call
     * itself after each command until the user ends the process or types
     * 'exit'.
     *
     * @return void
     */
    public function enableCLISession() {

        if (!$this->request->isFromCLI()) return;
        try {

            if (!$this->user->isLoggedIn()) {

                $this->config->parseArgvIntoGet();
                $this->user->login([
                    'un' => $_GET['un'],
                    'pw' => $_GET['pw'],
                ]);
                echo 'Welcome to the ActionRouter monitor.'."\n";
            }
            $this->holdCLISession();
        }
        catch (\Exception $e) {

            $this->logger->logError($e);
            echo $e->getMessage() . "\n";
        }
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
     * TODO: update for use with ControllerResponse
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

        if ($this->request->isAsync()) $this->flushContent();
        elseif ($this->serveContentOnly === true) $this->flushContent();
        else $this->flushAll();
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

        $data = $this->prepareAsyncErrorNotice($e);
        $cliMsg = $this->prepareCLIErrorNotice($e);
        $content = $this->preapreErrorPage($e);
        $response = new ControllerResponse(false, $cliMsg, $data, $content);

        $this->setResponse($response);
        $this->serveContent();
    }

    /**
     * TODO: make sure this works with ControllerResponse
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
     * @method callRequestedAction()
     * Call the action specified in the request via the appropriate controller.
     *
     * @return ControllerResponse
     * Returns the contents returned by the controller method specified in the
     * request.
     */
    private function callRequestedAction() {

        if (isset($this->action)) $action = $this->action;
        else throw new \Exception(__METHOD__.': No Action Given.');

        $response = $this->controller->{ $action }();

        if (!($response instanceof ControllerResponse)) {

            throw new \Exception(
                __METHOD__.': User-Defined controller must return an instance
                of class ControllerResponse.'
            );
        }

        return $response;
    }

    private function checkForCLIExit(array $param) {

        if (isset($param[1])) return;
        if ($param[0] === 'exit') exit('Bye.' . "\n");
    }

    /**
     * @method Router::holdCLISession
     * This method is responsible for asking the user to enter a command,
     * executing that command, and recursing until the 'exit' command.
     *
     * @return void
     */
    private function holdCLISession() {

        try {

            $this->updateRequest(readline('ActionRouter> '));
            $this->routeAndExecuteRequest(false);
        }
        catch (\Exception $e) {

            $this->logger->logError($e);
            echo $e->getMessage() . "\n";
        }
        $this->holdCLISession();
    }

    /**
     * @method Router::prepareAsyncErrorNotice
     * Serve an error notice in response to a failed asynchronous request.
     *
     * @param Exception $e
     * @return str
     */
    private function prepareAsyncErrorNotice(Exception $e) {

        $result = new AsyncResponse($this->user, [
            'success' => false,
            'title' => 'The server could not process your request.',
            'message' => $e->getMessage(),
        ]);
        return $res->getJSONResponse();
    }

    /**
     * @method Router::prepareCLIErrorNotice
     * Serve an error notice in response to a failed asynchronous request.
     *
     * @param Exception $e
     * @return str
     */
    private function prepareCLIErrorNotice(Exception $e) {

        return 'Request could not be completed.'."\n".$e->getMessage()."\n";
    }

    /**
     * @method Router::prepareErrorPage
     * Create an error notice in response to a failed (synchronous) request.
     * This method should return HTML for the error page body (that is, not
     * including the HTML of the page template).
     *
     * @param Exception $e
     * @return str
     */
    private function prepareErrorPage(Exception $e) {

        $ctrlr = $this->config->getDirective('controllers')
            ->{$this->request->getLabelOfRequestedController()};

        $template = $ctrlr->errorPageTemplate;
        $title = $ctrlr->errorPageHeading;
        $message = $e->getMessage();

        ob_start();
        require_once $template;
        return ob_get_clean();
    }

    /**
     * @method Router::requestLogin
     * Determine a method via which to request a login from the user.
     *
     * @param Request $request
     * The instance of the Request class which represents the current request.
     *
     * @param UserManager $user
     * The instance of the UserManager class which represents the current user.
     *
     * @return void
     */
    private static function requestLogin(Request $request, UserManager $user) {

        $login = ($request->isAsync()) ? 'askForAsyncLogin' : 'sendToLoginPage';
        $user->{ $login }();
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
    private function setController(IRequestController $controller) {

        $this->controller = $controller;
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
    private function setControllerAction($action) {

        $this->action = $action;
    }

    /**
     * @method Router::updateRequest
     * This method is intended to be used during CLI sessions when it becomes
     * necessary to accept multiple commands, which can be treated similarly
     * to requests. This method parses user-supplied commands and updates the
     * router's Request instance.
     *
     * @param str $str
     * The user-supplied command, e.g. "param1=val1 param2=val2"
     *
     * @return void
     */
    private function updateRequest($str) {

        $params = explode(" ", $str);
        $_GET = [];
        foreach ($params as $param) {

            $e = explode("=", $param);
            $this->checkForCLIExit($e);
            $this->validateCLIParam($e);
            $_GET[$e[0]] = $e[1];
        }

        $this->request = new Request($this->config);
    }

    private function validateCLIParam(array $param) {

        if (!isset($param[1])) {

            throw new \Exception(
                'Syntax error. Arguments must be separated by spaces'
                . ' and of the form param=value.'
            );
        }
    }
}

