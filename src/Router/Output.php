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

            $m = __METHOD__ . ' expects $metaDesc as string.';
            throw new \InvalidArgumentException($m, 406);
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

            $m = __METHOD__ . " expects the \$info array to include 'title', ";
            $m .= "'metaDesc', and 'section' nodes.";
            throw new \InvalidArgumentException($m, 408);
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

            $m = __METHOD__ . ' expects $section as string.';
            throw new \InvalidArgumentException($m, 407);
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

            $m = __METHOD__ . ' expects argument $templatePath as string.';
            throw new \InvalidArgumentException($m, 404);
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

            $m = __METHOD__ . ' expects $title as string.';
            throw new \InvalidArgumentException($m, 405);
        }

        $this->title = htmlspecialchars($title);
    }

    /**
     * TODO: adapt to ControllerResponse
     * @method Output::flushContent()
     * Flush the content set in the $content property to the client. Flushes
     * only the content, without any template.
     *
     * @return void
     * Sends the contents of the instance's $content property to the client.
     */
    protected function flushContent() {

        if (empty($this->response)) {

            $m = __METHOD__ . ': No content found.';
            throw new \BadMethodCallException($m, 408);
        }

        // TODO flush which content?
        echo $this->content;
    }

    /**
     * TODO: adapt to ControllerResponse
     * @method Output::flushAll()
     * Flush the content set in the $content property within the template set in
     * the $templatePath property to the client.
     *
     * @return void
     * Sends the template, along with the contents of the instance's $content
     * property, to the client.
     */
    protected function flushAll() {

        if (empty($this->templatePath)) {

            $m = __METHOD__ . ': No template path found.';
            throw new \BadMethodCallException($m, 401);
        }

        if (empty($this->content)) {

            $m = __METHOD__ . ': No content found.';
            throw new \BadMethodCallException($m, 402);
        }

        require_once $this->templatePath;
    }
}

