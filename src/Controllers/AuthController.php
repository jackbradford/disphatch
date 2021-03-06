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
            $data = (object) [
                'serverMessage' => $e->getMessage()
            ];
        }
        return new ControllerResponse($success, $message, $data);
    }

    // TODO: what kind of message should go into Exceptions? Just user-facing
    // messages?
    // Save the internal messages for error logs?
    private function validateData($data) {

        if ($data === null) {

            throw new \Exception("Data from client not found or invalid.");
        }
        if (!is_object($data)) {

            throw new \Exception("Data from client must be an object.");
        }
        return $data;
    }
}

