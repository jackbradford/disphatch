<?php
/**
 * @file Controllers/AuthController.php
 * This file provides the controller which handles authorization requests.
 *
 */
namespace JackBradford\Disphatch\Controllers;

class AuthController extends Controller implements IRequestController {

    public function auth() {

        $data = json_decode($this->fromGET('data'));
        $cred = [

            'un' => $data['un'],
            'pw' => $data['pw'],
        ];
        try {

            $this->userMgr->login($cred);
            $ud = $this->userMgr->getCurrentUser()->getDetails();
            $success = true;
            $message = 'Login successful.';
            $data = [
                'id' => $ud->id,
                'email' => $ud->email
            ];
        }
        catch (\Exception $e) {

            $success = false;
            $message = 'Login failed. ' . $e->getMessage();
            $data = [];
        }
        return new ControllerResponse($success, $message, $data);
    }
}

