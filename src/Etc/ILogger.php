<?php
/**
 * @file ILogger.php
 * This file provides a standard interface for the router's logging system.
 *
 * @interface ILogger
 * Having this interface affords the user control over the logging system. For
 * example, one could add support for their database.
 *
 */
namespace JackBradford\ActionRouter\Etc;

interface ILogger {

    public function __construct();

    public function logError();

    public function addEntryToDatabase(Exception $e);
}

