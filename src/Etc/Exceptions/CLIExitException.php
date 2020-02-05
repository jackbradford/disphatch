<?php
/**
 * @file Etc/Exceptions/CLIExitException.php
 *
 */
namespace JackBradford\Disphatch\Etc\Exceptions;

/**
 * @class CLIExitException
 * This class extends the standard Exception class. It is to be thrown when
 * a user issues the `exit` command to the CLI prompt.
 *
 */
class CLIExitException extends \Exception {}

