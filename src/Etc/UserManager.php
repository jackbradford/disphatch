<?php
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
namespace JackBradford\Disphatch\Etc;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use JackBradford\Disphatch\Config\Config;

class UserManager {

    protected $user = null;
    protected $request;
    protected $config;

    /**
     * @method UserManager::__construct()
     * Construct a new instance of UserManager.
     *
     * @param Request $request
     * An instance of the Request class, which represents the initial
     * request to the application.
     *
     * @param Config $config
     * An instance of the Config class, which is necessary for accessing
     * the settings defined in the application's .ini file.
     *
     * @return UserManager
     * Returns an instance of UserManager, initialized with the currently
     * logged-in user, if applicable.
     */
    public function __construct(Request $request, Config $config) {

        $this->request = $request;
        $this->config = $config;
        $this->user = ($this->isLoggedIn())
            ? $this->attemptGetLoggedInUser()
            : null;
    }

    /**
     * @method UserManager::authorizeAction()
     * Check for sufficient permission. When a user's request specifies an
     * "action," that action is managed by a method in the appropriate
     * controller. Each method may have different access requirements, and
     * this method affords the enforcement of those requirements.
     *
     * @param str $class
     * The class (response controller) to which the requested action belongs,
     * including the namespace it was declared in.
     *
     * @param str $method
     * The name of the method (action) to authorize.
     *
     * @return bool
     * Returns TRUE if permission has been granted, FALSE otherwise. If the
     * method given cannot be found in the permission/method map, an exception
     * will be thrown.
     */
    public function authorizeAction($class, $method) {

 //       if ($this->request->isForPublicResource()) return true;
        if (!$this->user) {
            throw new \Exception('User has not been set.');
        }

        $auths = $this->config->getDirective('permissions');

        if (!property_exists($auths, $class)) {

            throw new \Exception(
                'No permissions for the given controller could be found: '
                . $class
            );
        }

        if (!property_exists($auths->{$class}, $method)) {

            throw new \Exception(
                'No permissions for the given method could be found: '
                . $method
            );
        }

        foreach ($auths->{$class}->{$method} as $permission) {

            if (!$this->user->hasAccess($permission)) return false;
        }
        return true;

    }

    /**
     * @method UserManager::createUser
     * Creates a new user. Activating the user at the same time is optional.
     * Users must be activated before they may log in.
     *
     * @param str $fname
     * The user's first name.
     *
     * @param str $lname
     * The user's last name.
     *
     * @param str $email
     * The user's email.
     *
     * @param str $password
     * The user's password.
     *
     * @return User
     * Returns an instance of the User class.
     */
    public function createUser($fname, $lname, $email, $password) {

        if (!$user = Sentinel::create([

            'first_name' => $fname,
            'last_name' => $lname,
            'email' => $email,
            'password' => $password,
        ])) {

            throw new \Exception(
                'Could not create user. Ensure all necessary
                fields have been entered. '
            );
        }

        return new User($user);
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
        if ($this->request->isForPublicResource()) return true;
        if ($this->request->isAuthRequest()) return true;
        if ($this->request->isFromCLI()) return true;
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
     * @method UserManager::login
     * Attempt to log in a user.
     *
     * @param array $credentials
     * The array of credentials which contain the username ('un') and password
     * ('pw') of the user.
     *
     * @return void
     * Throws exception on error.
     */
    public function login(array $credentials) {

        $user = $this->findUser($credentials);
        $userIsValid = $this->validateUserCredentials($user, [
            'email' => $user->email,
            'password' => $credentials['pw']
        ]);

        if ($userIsValid) {

            $this->attemptLogin($user);
            $this->user = $this->attemptGetLoggedInUser();
        }
        else {

            throw new \Exception('Invalid Password.');
        }
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

            throw new \Exception('Could not end user session.');
        }
        else $this->user = null;
        return (Sentinel::check()) ? false : true;
    }

    /**
     * @method UserManager::getCurrentUser()
     * Get an object containing the current user's data.
     *
     * @return User
     * Returns null if there is no user currently logged in.
     */
    public function getCurrentUser() {

        return (!empty($this->user))
            ? $this->user
            : null;
    }

    /**
     * @method UserManager::getUser()
     * Load a User instance via the user's login credentials.
     *
     * @param str $email
     * The user's email associated with their account.
     *
     * @return User
     */
    public function getUser($email) {

        $user = Sentinel::findByCredentials(['login'=>$email]);
        if (is_null($user)) throw new \Exception('User not found.');
        return new User($user);
    }

    /**
     * @method UserManager::getUserById
     * Load a User instance via the user's id.
     *
     * @param int $id
     * The user record's id.
     *
     * @return User
     */
    public function getUserById($id) {

        return new User(Sentinel::findById($id));
    }

    /**
     * @method UserManager::currentUserHasAccess()
     * Check whether a user has access to a set of permissions.
     *
     * @param $permissions
     * The set of permissions to check for access.
     *
     * @return bool
     * Returns TRUE if the user has access, FALSE otherwise.
     */
    public function currentUserHasAccess($permissions) {

        return ($this->$user->hasAccess($permissions)) ? true : false;
    }

    private function attemptLogin($user) {

        if (!Sentinel::login($user)) {

            throw new \Exception('Login Error');
        }
    }

    /**
     * @method UserManager::deleteUser
     * Delete a user from the system.
     *
     * @param $userId
     * The ID of the user to be deleted.
     *
     * @return void
     */
    public function deleteUser($userId) {

        $user = $this->getUserById($userId);
        $user->permanentDelete();
    }

    /**
     * @method UserManager::attemptGetLoggedInUser
     * Attempt to create an instance of the User class and assign it to this
     * instance's $user property via the currently logged-in user.
     *
     * @return void
     */
    private function attemptGetLoggedInUser() {

        if (($loggedInUser = Sentinel::check()) !== false) {

            return new User($loggedInUser);
        }
        else {

            throw new \Exception(
                'Could not log in user: ' . $user->email . '.'
            );
        }
    }

    /**
     * @method UserManager::findUser
     * Find a user via given credentials.
     *
     * @param array $credentials
     * An array of credentials, containing the key ['un'].
     *
     * @return Cartalyst\Sentinel\Users\EloquentUser
     */
    protected function findUser(array $credentials) {

        $un = htmlentities($credentials['un'], ENT_QUOTES);
        $user = Sentinel::findByCredentials([
            'login' => $un,
        ]);

        if (!$user) {
            throw new \Exception('Invalid Username.');
        }

        return $user;
    }

    protected function validateUserCredentials($user, array $credentials) {

        return (Sentinel::validateCredentials($user, $credentials)) ? true : false;
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
//  public function loginAdmin(array $credentials = []) {
//
//      $credentials = (empty($credentials))
//          ?   $this->request->decodePostedJSON()
//          :   $credentials;
//
//      $this->login($credentials);
//      return true;
//  }

    /**
     * @method UserManager::loginGuest()
     * Log in the Guest user. The "Guest User" is the user that the
     * user-management system will assign to anonymous visitors for
     * e.g. authentication purposes.
     *
     * @return void
     * This method will throw an exception if authentication does not succeed.
     */
//  public function loginGuest() {
//
//      $guest          =   $this->settings->guest_user;
//      $credentials    = [
//          'un'=>'webguest',
//          'pw'=>$guest['password'],
//      ];
//      $this->login($credentials);
//  }

    /**
     * @method UserManager::loginCLIAdmin()
     * Log in the user which represents the use of the application via the
     * local CLI.
     *
     * @return void
     * This method will throw an exception if authentication does not succeed.
     */
//  public function loginCLIAdmin() {
//
//      $credentials = [
//          'un'=>'cli',
//          'pw'=>$this->settings->cli_user['password'],
//      ];
//      $this->login($credentials);
//  }

}
