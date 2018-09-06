<?php
/**
 * @file Etc/Exceptions/NotLoggedInException.php
 *
 */
namespace JackBradford\ActionRouter\Etc\Exceptions;

/**
 * @class NotLoggedInException
 * This class extends the standard Exception class. It is to be thrown when
 * a user attempts to access a system function which requires authorization.
 *
 */
class NotLoggedInException extends Exception {}

