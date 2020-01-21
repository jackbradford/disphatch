<?php
/**
 * @file Etc/Exceptions/SubmitItemException.php
 *
 */
namespace JackBradford\Disphatch\Etc\Exceptions;

/**
 * @class SubmitItemException
 * This class extends the standard Exception class. It should be thrown when
 * a submitted item cannot be processed.
 *
 * TODO: Determine if this exception is used in the router, or if it came from
 * its original parent application, ALE_IM.
 */
class SubmitItemException extends \Exception {}

