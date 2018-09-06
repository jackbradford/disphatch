<?php
/**
 * @file logger.php
 * This file provides an interface through which errors and other messages
 * can be recorded.
 *
 * @class Logger
 * This class is responsible for logging messages and errors, especially those
 * which result from exceptions, uncaught or otherwise. The class requires
 * access to a database to make the logs more accessible than they would be if
 * they existed in a log file only.
 */
namespace JackBradford\ActionRouter\Etc;

class Logger {

	private $db;
	private $e;

	/**
	 * @method Logger::__construct()
	 *
	 * @param Db $db
	 * An instance of the database-abstraction layer.
	 *
	 * @return Logger
	 * Returns an instance of this class, initialized with access to a 
	 * database.
	 */
	public function __construct(Db $db) {

		$this->db	=	$db;
	}

	/**
	 * @method Logger::logError()
	 * Log an error from an exception.
	 *
	 * @param Exception $e, str
	 * The exception representing the error to be logged. Also accepts a string,
	 * in which case this method will simply record that string.
	 *
	 * @return void
	 * This method always logs the Exception message (or given string) to the log
	 * file defined in config.php. It will also log all Exceptions, including
	 * message, error code, source file, line, and stack trace in a database entry.
	 */
	public function logError($e) {

		if (!is_object($e) && !is_string($e)) {

			$m	=	__METHOD__ . ' expects either an Exception or a String.';
			throw new InvalidArgumentException($m, 110);
		}

		$this->e	=	$e;
		$this->addEntryToLogFile();
		if (is_object($this->e)) $this->addEntryToDatabase();
	}

	private function addEntryToLogFile() {

		if (is_string($this->e)) error_log($this->e, 0);
		else {
			$m	=	$this->e->getMessage() . "\n Trace:\n";
			$m	.=	$this->e->getTraceAsString();
			error_log($m, 0);
		}
	}
	
	private function addEntryToDatabase() {

		$this->db->insertRecord('error_log')
			->fields([
				
				'fields'	=>	$this->createDatabaseFields([
			
					'code'		=>	$this->e->getCode(),
					'message'	=>	$this->e->getMessage(),
					'src_file'	=>	$this->e->getFile(),
					'line'		=>	$this->e->getLine(),
					'trace'		=>	$this->e->getTraceAsString(),
				]),
			])
			->execute();
	}

	private function createDatabaseFields(array $values) {

		$fields	=	[];

		foreach ($values as $field => $value) {

			$fields[]	=	new QueryField($field, null, $value);
		}

		return $fields;
	}
}

