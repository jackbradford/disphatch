<?php
/**
 * @file Controllers/AuthController.php
 * This file provides the controller which handles authorization requests.
 *
 */
namespace JackBradford\ActionRouter\Controllers;

class AuthControllers extends Controller implements IRequestController {

//    public function __toString() {
//
//        return 'auth';
//    }

    public function auth() {

        $data = json_decode($this->fromGET('data'));
        $cred = [

            'un' => $data['un'],
            'pw' => $data['pw'],
        ];
        try {

            $this->userMgr->login($cred);
            $success = true;
            $message = 'Login successful.';
        }
        catch (\Exception $e) {

            $success = false;
            $message = 'Login failed. ' . $e->getMessage();
        }
        return new ControllerResponse($success, $message);
    }
}

