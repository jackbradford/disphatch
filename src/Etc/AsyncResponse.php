<?php
/**
 * @file async_response.php
 * This file provides a class which represents an asynchronous response to
 * the client.
 *
 * @class AsyncResponse
 * This class is responsible for enforcing a standard mode of communication
 * with the client application.
 */
namespace JackBradford\ActionRouter\Etc;

class AsyncResponse {

    private $success;
    private $userIsLoggedIn;
    private $message;
    private $title;
    private $data;

    private function __clone() {}

    /**
     * @method AsyncResponse::__construct()
     * Create a new instance of this class.
     *
     * @param UserManager $user
     * The instance of the user manager.
     *
     * @param array $args
     * An array of response properties.
     *
     * @param bool $args['success']
     * Whether the initial request was successfully fulfilled (required).
     *
     * @param str $args['title']
     * The title of the response (optional).
     *
     * @param str $args['message']
     * The details/message related to the response (optional).
     *
     * @param mixed $args['data']
     * The data to be passed to the client (optional).
     *
     * @return AsyncResponse
     */
    public function __construct(UserManager $user, array $args) {

        $this->success = $this->validateSuccess($args);
        $this->userIsLoggedIn = $user->isLoggedIn();

        $this->message = (isset($args['message']))
            ? htmlentities($args['message'])
            : null;

        $this->title = (isset($args['title']))
            ? htmlentities($args['title'])
            : null;

        $this->data = (isset($args['data']))
            ? $args['data']
            : null;
    }
    
    /**
     * @method AsyncResponse::sendResponse()
     * Send a JSON-encoded response to the client.
     *
     * @return void
     * Emits a JSON object which contains the properties of the instance.
     */
    public function sendJSONResponse() {

        echo json_encode([

            'success' => $this->success,
            'userIsLoggedIn' => $this->userIsLoggedIn,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
        ]);
    }

    /**
     * @method AsyncResponse::getJSONResponse()
     * Returns a JSON-encoded response, as opposed to emitting it.
     *
     * @return str
     * Returns the output of json_encode() applied to an array of the class
     * instance's properties.
     */
    public function getJSONResponse() {

        return json_encode([
            
            'success' => $this->success,
            'userIsLoggedIn' => $this->userIsLoggedIn,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
        ]);
    }
    
    /**
     * @method AsyncResponse::setTitle()
     * Set the title of the response.
     *
     * @param str $title
     * The title of the response.
     *
     * @return void
     * Sets the $title property of the instance.
     */
    public function setTitle($title) {

        $this->title = htmlentities($title);
    }
    
    /**
     * @method AsyncResponse::setMessage()
     * Set the message/description of the response.
     *
     * @param str $msg
     * The message/description of the response.
     *
     * @return void
     * Sets the $message property of the instance.
     */
    public function setMessage($msg) {

        $this->message = htmlentities($msg);
    }

    /**
     * @method AsyncResponse::setData()
     * Set the data to be sent with the response.
     *
     * @param mixed $data
     * The data to send with the response.
     *
     * @return void
     * Sets the $data property of the instance.
     */
    public function setData($data) {

        $this->data = $data;
    }

    /**
     * @method AsyncResponse::validateSuccess
     * Check whether the 'success' argument is an expected value.
     *
     * @return bool
     */
    private function validateSuccess(array $args) {

        if (!isset($args['success']) || !is_bool($args['success'])) {

            $m = __METHOD__.': Argument for success status expects boolean.';
            throw new Exception($m);
        } 
        return $args['success'];
    }
}

