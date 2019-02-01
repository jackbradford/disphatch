<?php
/**
 * @file request.php
 * This file provides the Request class, which models the request from the 
 * client as it originated.
 *
 * @class Request
 * This class is responsible for representing the request, and for providing an
 * interface to other components of the system through which those components
 * can make queries regarding the specifications of the request.
 */
namespace JackBradford\ActionRouter\Etc;

use JackBradford\ActionRouter\Config\Config;

class Request {

    private $config;
    private $fieldMap;
    private $requestedPage;
    private $requestParameters;
    private $isFromCLI;

    private function __clone() {}

    /**
     * @method Request::__construct
     * Construct a new instance of the Request class to represent the original
     * request to the application.
     *
     * @param Config $config
     * An instance of the Config class.
     *
     * @return Request
     * Returns an instance of Request, initialized with each argument/value from
     * the original URL's query string loaded into the $requestParameters
     * property.
     */
    public function __construct(Config $config) {

        global $argv;
        $this->isFromCLI = (isset($argv)) ? true : false;
        $this->config = $config;
        $this->parseRequest();
    }

    /**
     * @method Request::isForPublicResource 
     * Check if the request is for a resource open to the public 
     * (i.e. authentication is not required for access).
     *
     * @return bool
     * If the request was for a public resource (as defined in the configuration
     * file) returns TRUE. Otherwise, returns FALSE.
     */
    public function isForPublicResource() {

        return $this->config->getDirective('controllers')
            ->{ $this->getLabelOfRequestedController() }
            ->isPublic;
    }

    /**
     * @method Request::isFromCLI
     * Check if the request is from the local command-line interface.
     *
     * @return bool
     * $argv will be set in the case PHP has been invoked via CLI. Otherwise, the
     * request will have originated elsewhere, and this method will return FALSE.
     */
    public function isFromCLI() {

        return $this->isFromCLI;   
    }

    /**
     * @method Request::isAsync
     * Check if the request originated from an asynchronous request, such as a
     * browser AJAX call.
     *
     * @return bool
     * Asynchronous requests to this system should POST an argument 'ajrq' with
     * value TRUE to the server so that it can be handled appropriately. The
     * presence of that argument will cause this method to return TRUE.
     */
    public function isAsync() {

        $asyncPostFlag = $this->config->getDirective('async_post_flag');
        return (isset($_POST[$asyncPostFlag])) ? true : false;
    }

    /**
     * @method Request::isAuthRequest
     * Check if the request was an attempt to authorize the user.
     *
     * @return bool
     * This method returns TRUE if the 'admin' controller and 'auth' action
     * were both specified in the initial request. Those parameters effect an
     * authorization attempt.
     */
    public function isAuthRequest() {

        return (

            $this->getLabelOfRequestedController() == 'auth'
            && $this->getNameOfRequestedAction() == 'auth'

        ) ? true : false;
    }

    /**
     * @method Request::getClassNameOfRequestedController
     * Get the class name of the requested controller.
     *
     * @return str
     */
    public function getClassNameOfRequestedController() {

        return $this->config->getDirective('controllers')
            ->{ $this->getLabelOfRequestedController() }
            ->class;
    }

    /**
     * @method Request::getLabelOfRequestedController
     * Get the label of the controller as it was specified in the initial 
     * request, and which corresponds to the controller's entry name in the
     * configuration file.
     *
     * @return str
     * Returns the label of the controller as specified in the initial request,
     * which is not mutable once set. If no controller was specified in the
     * request, the default controller label is returned.
     */
    public function getLabelOfRequestedController() {

        $controllers = $this->config->getDirective('controllers');
        $ctrlQueryStr = $this->config->getDirective('ctrl_query_str_label');

        if (!isset($this->requestParameters[$ctrlQueryStr])) {

            return $controllers->default;
        }

        $ctrl = $this->requestParameters[$ctrlQueryStr];

        if (!array_key_exists($ctrl, (array) $controllers)) {
            
            $m = __METHOD__ . ': Invalid controller requested.';
            throw new \Exception($m);
        }

        return $ctrl;
    }

    /**
     * @method Request::getNameOfRequestedAction
     * Get the name of the action specified in the initial request.
     *
     * @return str
     * Returns the name of the action specified in the initial request, which
     * is not mutable once set. If no action was specified in the request, the
     * default action, "home," is returned.
     */
    public function getNameOfRequestedAction() {

        $actionQueryStrLabel = $this->config
            ->getDirective('action_query_str_label');
        $defaultAction = $this->config
            ->getDirective('default_action');
        return (isset($this->requestParameters[$actionQueryStrLabel]))
            ? $this->requestParameters[$actionQueryStrLabel]
            : $defaultAction;
    }

    /**
     * @method Request::getRequestURL()
     * Get the query string of the URL which originated the request.
     *
     * @return str
     * Returns the query string of the URL.
     */
    public function getRequestURL() {

        $url = '?';
        foreach ($this->requestParameters as $param => $val) {

            $url .= "$param=$val&";
        }
        return substr($url, 0, -1);
    }

    /**
     * @method Request::parameterSpecified()
     * Check whether a given parameter was specified in the initial request.
     *
     * @param str $parameter
     * The parameter to search for within the request.
     *
     * @return mixed
     * Returns the value of the request parameter if it exists; returns FALSE
     * otherwise.
     */
    public function parameterSpecified($parameter) {

        return (array_key_exists($parameter, $this->requestParameters))
            ? $this->requestParameters[$parameter]
            : false;
    }

    /**
     * @method Request::decodePostedJSON()
     * Decode JSON data contained in the request via POST.
     *
     * @param str $postParam
     * The name of the POST parameter to which the JSON string was assigned.
     *
     * @return array
     * Returns decoded JSON data on success. If there is no JSON data in the
     * request, or if the method fails to decode it, an exception will be
     * thrown.
     */
    public function decodePostedJSON($postParam) {

        if (!isset($_POST[$postParam])) {
        
            throw new \Exception('JSON not found.');
        }

        $json = json_decode($_POST[$postParam], true);

        if (is_null($json) || !$json) {

            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        return $json;
    }

    private function parseRequest() {

        $params = [];
        foreach ($_GET as $key => $value) {

            $params[$key] = $value;
        }
        $this->requestParameters = $params;
    }
}

