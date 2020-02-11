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
namespace JackBradford\Disphatch\Router;

use JackBradford\Disphatch\Config\Config;
use JackBradford\Disphatch\Controllers\ControllerResponse;
use JackBradford\Disphatch\Controllers\IRequestController;
use JackBradford\Disphatch\Etc\Request;
use JackBradford\Disphatch\Etc\UserManager;
use JackBradford\Disphatch\Etc\Logger;
use JackBradford\Disphatch\Etc\Exceptions\CLIExitException;
use JackBradford\Disphatch\Etc\Exceptions\NotLoggedInException;
use JackBradford\Disphatch\Etc\RoutingDIContainer;

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
        catch (\Exception $e) {

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
        catch (\Exception $e) {

            $m = $e->getMessage() . "\n" . $e->getTraceAsString();
            error_log('Disphatch failed to initialize: '.$m);
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

        if ($this->request->isForPublicResource()) return;
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
            $this->setTemplateForController();
            $this->authorizeRequest();

            $response = (!$this->request->isAsync() && $serveClientAppOnSyncReq)
                ? $this->callClientApp()
                : $this->callRequestedAction();

            $this->setResponse($response);
        }
        catch (NotLoggedInException $e) {

            $this->setResponse($this->requestLogin());
        }
        catch (\Exception $e) {

            $this->logger->logError($e);
            $this->setResponse($this->getErrorNotice($e));
        }
        $this->serveContent();
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
        $clientApps = $this->config->getDirective('client_apps');
        if (!property_exists($clientApps, $label)) {

            throw new \Exception('No client app path found.');
        }
        $appPath = $clientApps->{$label};

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
                echo 'Welcome to the Disphatch monitor.'."\n";
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

        if ($this->request->isAsync()) $this->flushData($this->user);
        elseif ($this->request->isFromCLI()) $this->flushCLIMessage();
        elseif ($this->serveContentOnly === true) $this->flushContent();
        else $this->flushAll();
    }

    /**
     * @method getErrorNotice()
     * Return a controller response which indicates an error has occurred.
     *
     * @param Exception $e
     * The exception which could not be recovered from.
     *
     * @param bool $genericMsg
     * If true, send a generic error message. Otherwise, send the text of the
     * exception message.
     *
     * @return ControllerResponse
     */
    public function getErrorNotice(\Exception $e, $genericMsg=true) {

        if (!is_bool($genericMsg)) {

            throw new \InvalidArgumentException('Expected boolean.');
        }
        $data = $this->prepareAsyncErrorNotice($e, $genericMsg);
        $cliMsg = $this->prepareCLIErrorNotice($e);
        $content = $this->prepareErrorPage($e, $genericMsg);
        return new ControllerResponse(false, $cliMsg, $data, $content);
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
     * @method Router::askForAsyncLogin()
     * For the case that the user has previously logged in and is using e.g. a
     * client-based JS application, but the server session timed out since the
     * user's last request. This method returns a JSON response to be handled by
     * that client application.
     */
    public function askForAsyncLogin() {

        $msg = 'You must be logged in to complete this request.';
        $data = [
            'error_code' => 'login_required',
            'message' => $msg
        ];
        return new ControllerResponse(false, null, $data);
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
        else throw new \Exception('No Action Given.');

        try {

            $response = $this->controller->{ $action }();
        }
        catch (\Exception $e) {

            $response = $this->getErrorNotice($e, false);
        }

        if (!($response instanceof ControllerResponse)) {

            throw new \Exception(
                'User-Defined controller must return an instance '
                . 'of class ControllerResponse.'
            );
        }

        return $response;
    }

    private function checkForCLIExit(array $param) {

        if (isset($param[1])) return false;
        if ($param[0] === 'exit') return true;
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

            $this->updateRequest(readline('Disphatch> '));
            $this->routeAndExecuteRequest(false);
        }
        catch (CLIExitException $e) {

           exit('Bye.' . "\n");
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
     *
     * @param bool $genericMsg
     * If true, use the generic error message. Otherwise, use the exception
     * message.
     *
     * @return str
     */
    private function prepareAsyncErrorNotice(\Exception $e, $genericMsg) {

        $msg = ($genericMsg)
            ? 'The server could not complete your request.'
            : $e->getMessage();
        return [
            'error_code' => 'server_error',
            'message' => $msg
        ];
    }

    /**
     * @method Router::prepareCLIErrorNotice
     * Serve an error notice in response to a failed asynchronous request.
     *
     * @param Exception $e
     * @return str
     */
    private function prepareCLIErrorNotice(\Exception $e) {

        return 'Request could not be completed.'."\n".$e->getMessage()."\n";
    }

    /**
     * @method Router::prepareErrorPage
     * Generates and returns the error page content. The content is inserted
     * into the error page template defined in the config file for the
     * requested controller. The error page template should only contain the
     * layout for the main body of the page (i.e. excluding any headers,
     * footers, etc.). The error page template will, itself, be placed into
     * the page template defined in the config file.
     *
     * @param Exception $e
     *
     * @param bool $genericMsg
     * If true, use the generic error message. Otherwise, use the exception
     * message.
     *
     * @return str
     */
    private function prepareErrorPage(\Exception $e, $genericMsg) {

        $ctrlr = $this->config->getDirective('controllers')
            ->{$this->request->getLabelOfRequestedController()};

        $title = $ctrlr->errorPageHeading;
        $this->setTitle($title);
        $message = ($genericMsg)
            ? 'The server could not process your request.'
            : $e->getMessage();

        ob_start();
        require_once $ctrlr->errorPageTemplate;
        return ob_get_clean();
    }

    /**
     * @method Router::requestLogin
     * Determine a method via which to request a login from the user.
     *
     * @return ControllerResponse
     */
    private function requestLogin() {

        $this->toggleServeContentOnly(true); // TODO: should this be renamed toggleServeContentWithoutTemplate ?
        $login = ($this->request->isAsync())
            ? 'askForAsyncLogin'
            : 'sendToLoginPage';
        return $this->{ $login }();
    }

    /**
     * @method Router::sendToLoginPage()
     * Send a user to the login page.
     *
     * @return void
     * Loads the login page into the current output buffer.
     */
    private function sendToLoginPage() {

        ob_start();
        require_once $this->config->getDirective('login_page_path');
        return new ControllerResponse(true, null, [], ob_get_clean());
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
     * @method Router::setTemplateForController()
     * Use the requested controller to select a template for the outout.
     *
     * @return void
     */
    private function setTemplateForController() {

        if (!$this->request->isAsync()) {

            $ctrlrLabel = $this->request->getLabelOfRequestedController();
            $this->setTemplate($this->config->getDirective('controllers')->$ctrlrLabel->template);
        }
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
            if ($this->checkForCLIExit($e)) throw new CLIExitException();
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

