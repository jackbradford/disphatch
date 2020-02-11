<?php
/**
 * @file Controllers/AuthController.php
 * This file provides the controller which handles authorization requests.
 *
 */
namespace JackBradford\Disphatch\Controllers;

class AuthController extends Controller implements IRequestController {

    public function auth() {

        $data = $this->validateData(json_decode($this->fromPOST('data')));
        $cred = [

            'un' => $data->un,
            'pw' => $data->pw,
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

    private function validateData($data) {

        if ($data === null) {

            throw new \Exception("Data not found or invalid.");
        }
    }
}

