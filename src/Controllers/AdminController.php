<?php
/**
 * @file Controllers/AdminController.php
 * This file provides the controller which handles administrative actions.
 *
 */
namespace JackBradford\ActionRouter\Controllers;

class AdminController extends Controller implements IRequestController {

    /**
     * @method AdminController::addUser
     * Create a new user and actication record. Optionally, fully activate
     * the user.
     *
     * @param str $_GET['firstname']
     * The first name of the user, specified in the request.
     *
     * @param str $_GET['lastname']
     * The last name of the user.
     *
     * @param str $_GET['email']
     * The email of the user to associate with the account.
     *
     * @param str $_GET['password']
     * The password to use for the account.
     *
     * @param bool $_GET['activate'] (optional)
     * Defaults to 0 (false). If set to 1 (true), the user will be
     * activated.
     *
     * @return ControllerResponse
     */
    public function addUser() {

        $fn = $this->fromGET('firstname');
        $ln = $this->fromGET('lastname');
        $email = $this->fromGET('email');
        $pw = $this->fromGET('password');
        $user = $this->userMgr->createUser($fn, $ln, $email, $pw);
        $data = ['user'=>$user->getDetails()];
        $activate = (isset($_GET['activate']) && $_GET['activate'] === 1)
            ? true
            : false;

        if ($activate) {
            
            $this->fullyActivateUser($user);
            $cliMsg = 'User added and activated.';
        }
        else {

            $activation = $user->getActivation();
            $data['activation_code'] = $activation->getDetails()->code;
            $cliMsg = 'User added successfully. Activation code: '
                . $data['activation_code'];
        }

        return new ControllerResponse(true, $cliMsg, $data);
    }

    /**
     * @method AdminController::activateUser
     * Complete the activation of a user that has already been created.
     *
     * @param str $_GET['code']
     * The activation code created at the time the user was created.
     *
     * @param str $_GET['email']
     * The email associated with the user's account.
     *
     * @return ControllerResponse
     */
    public function activateUser() {

        $code = $this->fromGET('code');
        $email = $this->fromGET('email');
        $user = $this->userMgr->getUser($email);
        $user->completeActivation($code);

        return new ControllerResponse(true, 'User activated successfully.');
    }

    /**
     * @method AdminController::createActivation()
     * Create an activation record for a user. This must be done prior to
     * completing the activation with AdminController::activateUser(),
     * except in the case that the user was newly created.
     *
     * @param str $_GET['email']
     * The email associated with the user's account.
     *
     * @return ControllerResponse
     * The response will contain the activation code, which is necessary to
     * complete the activation.
     */
    public function createActivation() {

        $email = $this->fromGET('email');
        $user = $this->userMgr->getUser($email);
        $activation = $user->getActivation();
        $data = [
            'user' => $user->getDetails(),
            'code' => $activation->getDetails()->code,
        ];
        $cliMsg = 'Activation record created. Code: ' . $data['code'];

        return new ControllerResponse(true, $cliMsg, $data);
    }

    /**
     * @method AdminController::deactivateUser
     * Deactivate a user without removing the user from the system.
     *
     * @param str $_GET['email']
     * The email associated with the user's account.
     *
     * @return ControllerResponse
     */
    public function deactivateUser() {

        $email = $this->fromGET('email');
        $user = $this->userMgr->getUser($email);
        $user->deactivate();

        return new ControllerResponse(true, 'User deactivated successfully.');
    }

    /**
     * @method AdminController::getUserDetails
     * Get the data associated with the user's account record.
     *
     * @param str $_GET['email']
     * The email associated with the user's account.
     *
     * @return ControllerResponse
     */
    public function getUserDetails() {

        $email = $this->fromGET('email');
        $user = $this->userMgr->getUser($email);
        $data = json_decode(json_encode($user->getDetails()), true);
        $cliMessage = "Details for user ".$user->getFullName().":";

        return new ControllerResponse(true, $cliMessage, $data);
    }

    /**
     * @method AdminController::updateUser
     * Update the user record.
     *
     * @param str $_GET['id']
     * The id of the user record.
     *
     * @param str $_GET['email'] (optional)
     * The user's updated email address.
     *
     * @param str $_GET['firstname']
     * The user's updated first name.
     *
     * @param str $_GET['lastname']
     * The user's updated last name.
     *
     * @return ControllerResponse
     */
    public function updateUser() {

        $id = $this->fromGET('id');
        $user = $this->userMgr->getUserById($id);
        $creds = [];
        $updates = [

            'email' => $this->fromGET('email'),
            'first_name' => $this->fromGET('fisrtname'),
            'last_name' => $this->fromGET('lastname'),
        ];

        foreach ($updates as $key => $value) {

            if (!is_null($value)) $creds[$key] = $value;
        }

        $user->update($creds);
        $cliMsg = 'User updated successfully.';

        return new ControllerResponse(true, $cliMsg);
    }

    /**
     * @method AdminController::fullyActivateUser
     * Fully activate a user by automatically entering the activation code.
     *
     * @param User $user
     * The user to activate.
     *
     * @return void
     * Throws an Exception if the user cannot be activated.
     */
    private function fullyActivateUser(User $user) {

        $activation = $user->getActivation();
        if (!$user->completeActivation($activation->getDetails()->code)) {

            throw new \Exception(__METHOD__ . ': Could not activate user.');
        }
    }
}

