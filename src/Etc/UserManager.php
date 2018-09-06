<?php

use Cartalyst\Sentinel\Native\Facades\Sentinel;

/**
 * @file user_manager.php
 * This file provides an abstraction layer to the library being used to manage
 * users.
 *
 * @class UserManager
 * This class provides an interface through which User settings can be affected.
 * It is responsible for authorizing user, performing tasks related to the user,
 * and providing information about the user.
 */
namespace JackBradford\ActionRouter\Etc;

class UserManager {

	protected $user	=	null;
	protected $request;
	protected $settings;

	/**
	 * @method UserManager::__construct()
	 * Construct a new instance of UserManager.
	 *
	 * @param Request $request
	 * An instance of the Request class, which represents the initial
	 * request to the application.
	 *
	 * @param Settings $settings
	 * An instance of the Settings class, which is necessary for accessing
	 * the settings defined in the application's .ini file.
	 *
	 * @return UserManager
	 * Returns an instance of UserManager, initialized with the currently
	 * logged-in user, if applicable.
	 */
	public function __construct(Request $request, Settings $settings) {

		$this->request	= $request;
		$this->settings	= $settings;
		$this->user	= ($this->isLoggedIn()) ? Sentinel::check() : null;	
	}

	/**
	 * @method UserManager::askForAsyncLogin()
	 * Complete an asynchronous request with a response directing the user of the
	 * remote client to log in. This may be necessary e.g. when a user's session
	 * has expired, requiring re-authentication, during the course of using the
	 * client-side interface.
	 */
	public static function askForAsyncLogin() {

		// For the case that the user has previous logged in, but the session
		// has timed out. This method should ask the user to login again.
	}

	public static function askForCLILogin() {

		$message	=	"\nNot Authorized:\nPlease log in as an administrator ";
		$message	.=	"before accessing the system via the command line.\n\n";
		$message	.=	"To request log-in:\n";

		echo $message;
	}

	/**
	 * @method UserManager::authorize()
	 * Authorize a user's request. When a user's request specifies an "action,"
	 * that action is managed by a method in the appropriate controller. Each
	 * method may have different access requirements, and this method affords
	 * the enforcement of those requirements.
	 *
	 * @param str $method
	 * The method to authorize.
	 *
	 * @return bool
	 * Returns TRUE if authorization has been granted, FALSE otherwise. If the
	 * method given cannot be found in the permission/method map, an exception
	 * will be thrown.
	 */
	public function authorize($method) {

		if (!$this->user) {
			throw new Exception(__METHOD__ . ': User not set.');
		}

		// TODO: put this in the config file
		$auths	=	Settings::getDirective(); 
		$auths	=	[

			'Inventory::commitItem'				=>	'inventory.commit',
			'Inventory::deleteItem'				=>	'inventory.delete',
			'Inventory::assignStatus'			=>	'inventory.status',
			'InvItem::updateItemStatus'			=>	'inventory.status',
			'AdminController::showList'			=>	'inventory.view_list',
			'AdminController::showItemData'		=>	'inventory.view_details',
			'AdminController::updateInvItem'	=>	'inventory.edit',
			'AdminController::getInvAssetData'	=>	'inventory.view_details',
			'AdminController::itemImport'		=>	'inventory.import',
			'AdminController::submitItemImport'	=>	'inventory.import',
			'AdminController::modifyExportList'	=>	'inventory.export',
			'AdminController::getCSV'			=>	'inventory.export',
			'AdminController::prepareExport'	=>	'inventory.export',
		];

		if (array_key_exists($method, $auths)) {

			$auth = ($this->user->hasAccess([$auths[$method]])) ? true : false;
			return $auth;

		} else {

			$m	=	__METHOD__ . ': No permissions for the given function could be found. ';
			throw new Exception($m);
		}
	}
	
	/**
	 * @method UserManager::isAuthorizedToMakeRequest
	 * Checks whether the incoming request can be executed. This is distinct
	 * from UserManager::authorize(), which authorizes specific methods within
	 * the controllers to which a registered user has a level of access.
	 *
	 * @return bool
	 * If the user is logged in, is making a log-in request, or is requesting a
	 * public page, returns true. Otherwise returns false.
	 *
	 */
	public function isAuthorizedToMakeRequest() {

		if ($this->isLoggedIn()) return true;
		if ($this->request->isFromGuest()) return true;
		if ($this->request->isAuthRequest()) return true;
		return false;
	}

	/**
	 * @method UserManager::isLoggedIn()
	 * Checks whether a user a logged in.
	 *
	 * @return bool
	 * Retruns TRUE if a user is logged in. FALSE if otherwise.
	 */
	public function isLoggedIn() {

		return (Sentinel::check() !== false) ? true : false;
	}

	/**
	 * @method UserManager::loginAdmin()
	 * Log in the administrative user.
	 *
	 * @param array $credentials
	 * The array of credentials which contain the username ('un') and password
	 * ('pw') of the user.
	 *
	 * @return bool
	 * Returns true on success. Exception(s) are thrown if the authentication
	 * does not succeed.
	 */
	public function loginAdmin(array $credentials = []) {

		$credentials = (empty($credentials))
			?	$this->request->decodePostedJSON()
			:	$credentials;

		$this->login($credentials);
		return true;
	}

	/**
	 * @method UserManager::loginGuest()
	 * Log in the Guest user. The "Guest User" is the user that the
	 * user-management system will assign to anonymous visitors for
	 * e.g. authentication purposes.
	 *
	 * @return void
	 * This method will throw an exception if authentication does not succeed.
	 */
	public function loginGuest() {

		$guest			=	$this->settings->guest_user;
		$credentials	= [
			'un'=>'webguest',
			'pw'=>$guest['password'],
		];
		$this->login($credentials);
	}

	/**
	 * @method UserManager::loginCLIAdmin()
	 * Log in the user which represents the use of the application via the
	 * local CLI.
	 *
	 * @return void
	 * This method will throw an exception if authentication does not succeed.
	 */
	public function loginCLIAdmin() {

		$credentials = [
			'un'=>'cli',
			'pw'=>$this->settings->cli_user['password'],
		];
		$this->login($credentials);
	}

	/**
	 * @method UserManager::logout()
	 * Log a user out of the system.
	 *
	 * @return bool
	 * Returns TRUE upon successful logout, FALSE if a user remains logged in.
	 */
	public function logout() {

		if (!Sentinel::logout()) {

			$m	=	__METHOD__ . ': Could not end user session.';
			throw new Exception($m);
		} 
		else $this->user = null;
		return (Sentinel::check()) ? false : true;
	}

	/**
	 * @method UserManager::getEmail()
	 * Get the current user's email address.
	 *
	 * @return str
	 */
	public function getEmail() {

		return $this->user->email;
	}

	/**
	 * @method UserManager::getFullName()
	 * Get the current user's full name.
	 *
	 * @return str
	 */
	public function getFullName() {

		return $this->user->first_name . ' ' . $this->user->last_name;
	}

	/**
	 * @method UserManager::getId()
	 * Get the current user's ID.
	 *
	 * @return int
	 */
	public function getId() {

		return $this->user->id;
	}
	
	/**
	 * @method UserManager::getUserData()
	 * Get an object containing the current user's data.
	 *
	 * @return mixed
	 */
	public function getUserData() {

		return (!empty($this->user)) ? $this->user : null;
	}

	/**
	 * @method UserManager::hasAccess()
	 * Check whether a user has access to a set of permissions.
	 *
	 * @param $permissions
	 * The set of permissions to check for access.
	 *
	 * @return bool
	 * Returns TRUE if the user has access, FALSE otherwise.
	 */
	public function hasAccess($permissions) {

		return ($user->hasAccess($permissions)) ? true : false;
	}

	/**
	 * @method UserManager::sendToLoginPage()
	 * Send a user to the login page.
	 *
	 * @return void
	 * Loads the login page into the current output buffer.
	 */
	public static function sendToLoginPage() {

		require_once 'admin_login.php';
	}

	protected function login(array $credentials) {

		$user		=	$this->findUser($credentials);
		$userIsValid=	$this->validateUserCredentials($user, [
			'email'		=>	$user->email,
			'password'	=>	$credentials['pw']
		]);

		if ($userIsValid) {

			$this->attemptLogin($user);
			$this->attemptSetLoggedInUser();
		} 
		else {

			throw new Exception('Invalid Password ('.$credentials['pw'].').');
		}
	}

	private function attemptLogin($user) {

		if (!Sentinel::login($user)) {

			throw new Exception('Login Error');
		}
	}

	private function attemptSetLoggedInUser() {

		if (($loggedInUser = Sentinel::check()) !== false) {

			$this->user = $loggedInUser;
		} 
		else {

			$message = __METHOD__.': Could not log in user: '.$user->email.'.';
			throw new Exception($message);
		}
	}

	protected function findUser(array $credentials) {

		$un		=	htmlentities($credentials['un'], ENT_QUOTES) . '@atlanticlabequipment.com';
		$user	=	Sentinel::findByCredentials([
			'login' => $un,
		]);

		if (!$user) {
			throw new Exception(__METHOD__.': Invalid Username.');
		}

		return $user;
	}

	protected function validateUserCredentials($user, array $credentials)
	{
		return (Sentinel::validateCredentials($user, $credentials)) ? true : false;
	}
}
