<?php
/**
 * @file output.php
 * This file provides a class for handling the output of the application.
 *
 * @class Output
 * This class is responsible for managing the output of the application, including
 * capturing any output from e.g. output buffers, as well as managing the properties
 * of the final page (in the case of synchronous page requests), like the title
 * and meta description.
 */
namespace JackBradford\ActionRouter\Router;

use JackBradford\ActionRouter\Controllers\ControllerResponse;
use JackBradford\ActionRouter\Etc\AsyncResponse;
use JackBradford\ActionRouter\Etc\UserManager;

class Output {

    protected $config;
    protected $response;
    protected $templatePath = null;
    protected $metaDesc = null;
    protected $title = null;
    protected $section = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * @method Output::setResponse()
     * Set the content of the page, or the content to be returned to the client
     * as part of an asynchronous request.
     *
     * @param ControllerResponse $response
     * Contains HTML/JSON/etc. content to be stored until it is output.
     *
     * @return void
     * Sets the instance's $content property.
     */
    public function setResponse(ControllerResponse $response) {

        $this->response = $response;
    }

    /**
     * @method Output::setMetaDesc()
     * Set the meta description of the page. The HTML template specified should
     * use this description. Not applicable to asynchronous requests.
     *
     * @param str $metaDesc
     * The page's meta description.
     *
     * @return void
     * Sets the instance's $metaDesc property.
     */
    public function setMetaDesc($metaDesc) {

        if (!is_string($metaDesc)) {

            throw new \InvalidArgumentException(
                'Invalid page meta description: expects string.'
            );
        }

        $this->metaDesc = htmlspecialchars($metaDesc);
    }

    /**
     * @method Output::setPageInfo()
     * Set the data associated with the requested page.
     *
     * @param array $info
     * An array containing the page information. Available nodes include 'title'
     * (page title), 'metaDesc' (page meta description), and 'section' (the
     * section of the site in which the page exists).
     *
     * @return void
     * Sets the appropriate properties of the class.
     */
    public function setPageInfo(array $info) {

        if (
            !isset($info['title']) 
            || !isset($info['metaDesc']) 
            || !isset($info['section'])
        ) {

            throw new \InvalidArgumentException(
                'Invalid array: must include \'title\', \'metaDesc\', and '
                . '\'section\' nodes.'
            );
        }

        $this->setTitle($info['title']);
        $this->setMetaDesc($info['metaDesc']);
        $this->setSection($info['section']);
    }

    /**
     * @method Output::setSection()
     * Set the current section of the site in which the page exists. The HTML
     * template specified will use this section. Not applicable to asynchronous
     * requests.
     *
     * @param str $section
     * The site section in which the requested page exists.
     *
     * @return void
     * Set the instance's $section property.
     */
    public function setSection($section) {

        if (!is_string($section)) {

            throw new \InvalidArgumentException(
                'Invalid section name: expected string.'
            );
        }

        $this->section = htmlspecialchars($section);
    }

    /**
     * @method Output::setTemplate()
     * Set the HTML template file to be used when serving content as part of a
     * synchronous page request.
     *
     * @param str $templatePath
     * The filepath of the HTML template to be used to serve the content.
     *
     * @return void
     * Sets the instance's $templatePath property.
     */
    public function setTemplate($templatePath) {

        if (!is_string($templatePath)) {

            throw new \InvalidArgumentException(
                'Invalid template path: expected string.'
            );
        }

        $this->templatePath = $templatePath;
    }

    /**
     * @method Output::setTitle()
     * Set the title of the page. The HTML template specified will use this
     * title. Not applicable to asynchronous requests.
     *
     * @param str $title
     * The title of the page.
     *
     * @return void
     * Sets the instance's $title property.
     */
    public function setTitle($title) {

        if (!is_string($title)) {

            throw new \InvalidArgumentException(
                'Invalid page title: expected string.'
            );
        }

        $this->title = htmlspecialchars($title);
    }

    /**
     * @method Output::checkResponseExists()
     * Check that a controller response has been set.
     *
     * @return void
     * Throws an Exception if no response exists.
     */
    protected function checkResponseExists() {

        if (empty($this->response)) {

            throw new \Exception(
                'Could not flush content: No response found.'
            );
        }
    }

    /**
     * @method Output::flushCLIMessage()
     * Send the message defined in the controller response to the console.
     *
     * @return void
     * Emits a string.
     */
    protected function flushCLIMessage() {

        $this->checkResponseExists();
        if (($msg = $this->response->getCLIMessage()) === null) {

            throw new \Exception(
                'Could not flush CLI Message: response contained no message.'
            );
        }
        echo $msg . "\n";
    }

    /**
     * @method Output::flushContent()
     * Emit the content defined in the controller response. Emits only the
     * content, without any template.
     *
     * @return void
     * Emits a string.
     */
    protected function flushContent() {

        $this->checkResponseExists();
        if (($content = $this->response->getContent()) === null) {

            throw new \Exception(
                'Could not flush content: response contained no content.'
            );
        }
        echo $content;
    }

    /**
     * @method Output::flushData()
     * Emit the data object defined in the controller response in reply to
     * e.g. an asynchronous request from a client application.
     *
     * @param UserManager $userMgr
     *
     * @return void
     * Emits an object/string.
     */
    protected function flushData(UserManager $userMgr) {

        $this->checkResponseExists();
        $resp = new AsyncResponse($userMgr, [

            'success' => $this->response->isSuccess(),
            'data' => $this->response->getData(),
        ]);
        $resp->sendJSONResponse();
    }

    /**
     * @method Output::flushAll()
     * Emit the content defined in the controller response in the defined
     * template.
     *
     * @return void
     * Emits the template, along with the content defined in the controller
     * response.
     */
    protected function flushAll() {

        $this->checkResponseExists();
        if (empty($this->templatePath)) {

            throw new \BadMethodCallException(
                'Could not flush content: No template path found.'
            );
        }

        if (($content = $this->response->getContent()) === null) {

            throw new \BadMethodCallException(
                'Could not flush content: No content found.'
            );
        }

        require_once $this->templatePath;
    }
}

