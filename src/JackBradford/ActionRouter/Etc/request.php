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
class Request {

	private	$fieldMap;
	private	$requestedPage;
	private	$requestParameters;

	private function __clone() {}

	/**
	 * @method Request::__construct()
	 * Construct a new instance of the Request class to represent the original
	 * request to the application.
	 *
	 * @return Request
	 * Returns an instance of Request, initialized with each argument/value from
	 * the original URL's query string loaded into the $requestParameters
	 * property.
	 *
	 * TODO: Figure out if $argv is actually needed; I suspect it is not.
	 */
	public function __construct() {

		global $argv;
		$this->parseRequest();
	}

	/**
	 * @method Request::isFromGuest() 
	 * Check if the request is from a user of the (now decomissioned) website.
	 *
	 * @return bool
	 * If the request was for a public resource, returns TRUE. Otherwise, e.g. in
	 * the case of a request to ALE.IM, returns FALSE.
	 */
	public function isFromGuest() {

		return ($this->getNameOfRequestedController() == 'public') ? true : false;
	}

	/**
	 * @method Request::isFromCLI()
	 * Check if the request is from the local command-line interface.
	 *
	 * @return bool
	 * $argv will be set in the case PHP has been invoked via CLI. Otherwise, the
	 * request will have originated elsewhere, and this method will return FALSE.
	 */
	public function isFromCLI() {

		return (isset($argv)) ? true : false;
	}

	/**
	 * @method Request::isAsync()
	 * Check if the request originated from an asynchronous request, such as a
	 * browser AJAX call.
	 *
	 * @return bool
	 * Asynchronous requests to this system should POST an argument 'ajrq' with
	 * value TRUE to the server so that it can be handled appropriately. The
	 * presence of that argument will cause this method to return TRUE.
	 */
	public function isAsync() {

		return (isset($_POST['ajrq'])) ? true : false;
	}

	/**
	 * @method Request::isAuthRequest()
	 * Check if the request was an attempt to authorize the user.
	 *
	 * @return bool
	 * This method returns TRUE if the 'admin' controller and 'auth' action
	 * were both specified in the initial request. Those parameters effect an
	 * authorization attempt.
	 */
	public function isAuthRequest() {

		return (

			(isset($_GET['controller']) && $_GET['controller'] == 'admin')
			&& (isset($_GET['action']) && $_GET['action'] == 'auth')

		) ? true : false;
	}

	/**
	 * @method Request::getNameOfRequestedController()
	 * Get the name of the controller specified in the initial request.
	 *
	 * @return str
	 * Returns the name of the controller specified in the initial request,
	 * which is not mutable once set. If no controller was specified in the
	 * request, the default controller, "public," is returned.
	 */
	public function getNameOfRequestedController() {

		if (!isset($this->requestParameters['controller'])) {

			return DEFAULT_CONTROLLER;
		}

		$ctrl	=	$this->requestParameters['controller'];
		if (array_key_exists($ctrl, CONTROLLERS)) return $ctrl;
		else {

			$m	=	__METHOD__ . ': Invalid controller requested.';
		}
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

		return (isset($this->requestParameters['action']))
			?	$this->requestParameters['action']
			:	'home';
	}

	/**
	 * @method Request::getRequestedPage()
	 * Get the value of the "page" parameter from the initial request.
	 *
	 * @return str
	 * Returns the value of the "page" parameter as it appeared in the initial
	 * request. Returns NULL if that parameter was not specified.
	 */
	public function getRequestedPage() {

		return (isset($this->requestParameters['page']))
			? $this->requestParameters['page']
			: null;
	}

	/**
	 * @method Request::getRequestURL()
	 * Get the query string of the URL which originated the request.
	 *
	 * @return str
	 * Returns the query string of the URL.
	 */
	public function getRequestURL() {

		$url	=	'?';
		foreach ($this->requestParameters as $param => $val) {

			$url	.=	"$param=$val&";
		}
		$url	=	substr($url, 0, -1); // Removes last ampersand.
		return $url;
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
	 * @return array
	 * Returns decoded JSON data on success. If there is no JSON data in the
	 * request, or if the method fails to decode it, an exception will be
	 * thrown.
	 */
	public function decodePostedJSON()
	{
		if (!isset($_POST['json'])) {
			throw new Exception('JSON not found.');
		}
		$json	=	json_decode($_POST['json'], true);
		if (is_null($json) || !$json)
		{
			throw new Exception('JSON decode error: ' . json_last_error_msg());
		}
		return $json;
	}

	private function parseRequest()
	{
		$params = [];
		foreach ($_GET as $key => $value) {
			$params[$key] = $value;
		}
		$this->requestParameters = $params;
	}
}

