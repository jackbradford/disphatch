<?php
/**
 * @file
 * This file provides a database-access abstraction layer.
 *
 */
class Db {

	private	$database;
	private	$username;
	private	$hostname;
	private	$conn;
	private	$schema;

	private	$baseTable;
	private	$conditions		=	[];
	private	$fields			=	[];
	private	$joins			=	[];
	private	$options		=	[];
	private	$orderBy		=	[];
	private $pdoStmt;
	private $qp;
	private	$queryType;

	const INSERT	=	'insert';
	const SELECT	=	'select';
	const UPDATE	=	'update';
	const DEL		=	'delete';
	const ASC		=	'ASC';
	const DESC		=	'DESC';

	const EXEC_HANDLERS	=	[
		
		self::SELECT	=>	'prepareSelectStmt',
		self::INSERT	=>	'prepareInsertStmt',
		self::UPDATE	=>	'prepareUpdateStmt',
		self::DEL		=>	'prepareDeleteStmt',
	];

	/**
	 * @method __construct()
	 * Create a new instance of class Db, representing a connection to
	 * a database.
	 *
	 * @param str $hn
	 * The hostname of the database server.
	 *
	 * @param str $pw
	 * The password for the given database username.
	 *
	 * @param str $db
	 * The name of the database. Defaults to the DATABASE constant defined
	 * in config.php.
	 *
	 * @param str $un
	 * The database user. Defaults to the GUEST constant defined in config.php.
	 *
	 * @param QueryPreparer $qp
	 * An instance of QueryPreparer, which is responsible for preparing queries
	 * based on the inputs to this class.
	 *
	 * @return Db
	 */
	public function __construct($hn, $pw, $db = DATABASE, $un = GUEST, IQueryPreparer $qp) {

		$this->database	=	$db;
		$this->username	=	$un;
		$this->hostname	=	$hn;
		$this->qp		=	$qp;
		$this->conn		=	$this->establishConnection($pw);
		$this->schema	=	$this->loadSchema();
	}

	/**
	 * @method condition()
	 * Add a condition to the query, e.g. part of a WHERE clause.
	 *
	 * @param str $field
	 * The field (including table, if applicable) on which to apply the
	 * condition.
	 *
	 * @param str $operator
	 * The operator used to test the condition.
	 *
	 * @param str $value
	 * The value to compare the field against.
	 *
	 * @param str $conjunction
	 * In the case that more than one condition is added to a query,
	 * a conjunction, e.g. OR, AND, etc., should be passed.
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function condition($field, $operator, $value, $conjunction=null) {

		if (count($this->conditions) > 0 && $conjunction===null) {

			$message = __METHOD__ . ': Missing conjunction.';
			throw new Exception($message);
		}

		if (is_array($value)) {

			$m	=	__METHOD__ . ' $value argument may not be an array.';
			throw new Exception($m);
		}

		if (count($this->conditions) === 0) $conjunction = null;

		$this->conditions[]	=	(object)[
			'field'			=>	$field,
			'operator'		=>	$operator,
			'value'			=>	$value,
			'conjunction'	=>	$conjunction,
		];

		return $this;
	}

	/**
	 * @method deleteRecords()
	 * Construct a DELETE statement to remove records from a table.
	 *
	 * @param str $table
	 * The name of the table from which to delete records.
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function deleteRecords($table) {

		$this->resetQuery();
		$this->queryType = self::DEL;

		if ($this->tableExists($table)) {

			$this->baseTable = $table;
		}

		return $this;
	}

	/**
	 * @method execute()
	 * Construct and execute the query.
	 *
	 * @return Db
	 * Returns the current instance. Throws an exception if the statement
	 * fails to execute.
	 */
	public function execute() {

		$handler	=	self::EXEC_HANDLERS[$this->queryType];
		$prepStmt	=	$this->qp->{$handler}( (object) [
			
			'baseTable'	=>	$this->baseTable,
			'conditions'=>	$this->conditions,
			'fields'	=>	$this->fields,
			'joins'		=>	$this->joins,
			'options'	=>	$this->options,
			'orderBy'	=>	$this->orderBy,
			'queryType'	=>	$this->queryType,
		]);
		$pdoStmt	=	$this->conn->prepare($prepStmt->getQuery());

		if ($pdoStmt && $pdoStmt->execute($prepStmt->getParams())) {

			$this->pdoStmt	=	$pdoStmt;
			return $this;

		} else {

			$e	=	$pdoStmt->errorInfo();
			$m	=	__METHOD__ . ": $this->queryType failed on execute(). "
					. $e[2];
			throw new Exception($m);
		}
	}

	/**
	 * @method fetchAllResults()
	 * Get an array containing every result of a SELECT query.
	 *
	 * @return array
	 * Returns an array of associative arrays. In each row, a property may
	 * be accessed via the field name (or alias, if specified).
	 */
	public function fetchAllResults() {

		if ($this->pdoStmt === null) {

			$m = __METHOD__ . ': No PDO Statement.';
			throw new Exception($m);
		}

		return $this->pdoStmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * @method fields()
	 * Specify fields to affect the query.
	 *
	 * @param str $table
	 * A string representing the table name to apply the fields to in the case
	 * of SELECT statements. In the case of other query types, this parameter 
	 * may be omitted; the first argument, if it is passed as an array, will be
	 * used as the $fields parameter.
	 *
	 * @see QueryField
	 * @param array $fields
	 * An array of QueryField instances.
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function fields($options) {

		if ($this->queryType === self::DEL) {

			$message	=	__METHOD__ . ': DELETE statements do not require fields.
							Consider using a WHERE clause instead.';
			throw new Exception($message);
		}

		$table	=	(isset($options['table'])) ? $options['table'] : null;
		$fields	=	(isset($options['fields'])) ? $options['fields'] : [];
		$this->validateListOfFields($fields);
		$this->addFields($table, $fields);
		return $this;
	}

	/**
	 * @method insertRecord()
	 * Construct an INSERT statement to add a record to a table.
	 *
	 * @param str $table
	 * The name of the table in which to insert the record.
	 *
	 * @param array $options
	 * An array of options which affect the operation of the query.
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function insertRecord($table, array $options=[]) {

		$this->resetQuery();
		$this->queryType	=	self::INSERT;
		$this->options		=	$options;

		if ($this->tableExists($table)) {

			$this->baseTable	=	$table;
		}

		return $this;
	}

	/**
	 * @method joinTable()
	 * Construct a JOIN clause to add to the query.
	 *
	 * @param str $type
	 * The type of JOIN. E.g., LEFT, RIGHT, INNER, OUTER, etc.
	 *
	 * @param str $table
	 * The table to join.
	 *
	 * @param str $on
	 * The field of the JOINed table against which to check the condition.
	 *
	 * @param str $operator
	 * The operator for the condition.
	 *
	 * @param str $compareTo
	 * The field to compare the $on field against.
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function joinTable($type, $table, $on, $operator, $compareTo) {

		$this->joins[]	=	(object)[

			'type'		=>	$type,
			'table'		=>	$table,
			'on'		=>	$on,
			'operator'	=>	$operator,
			'compareTo'	=>	$compareTo,
		];

		return $this;
	}

	/**
	 * @method orderBy()
	 * Order the results of the query by a list of fields.
	 *
	 * @param $field
	 * The field by which to sort the results.
	 *
	 * @param $direction
	 * The direction of the sort. Accepts "ASC" or "DESC", and defaults
	 * to "ASC."
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function orderBy($field, $direction = self::ASC) {

		$this->orderBy[]	=	(object)[

			'field'		=>	$field,
			'direction'	=>	$direction
		];

		return $this;
	}

	/**
	 * @method select()
	 * Construct a SELECT statement to load records from a table.
	 *
	 * @param str $table
	 * The name of the table from which to select.
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function select($table) {

		$this->resetQuery();
		$this->queryType	=	self::SELECT;

		if ($this->tableExists($table)) {

			$this->baseTable	=	$table;
		}

		return $this;
	}

	/**
	 * @method update()
	 * Construct an UPDATE statement to update records in a table.
	 *
	 * @param str $table
	 * The name of the table to update.
	 *
	 * @return Db
	 * Returns the current instance of the class.
	 */
	public function update($table) {

		$this->resetQuery();
		$this->queryType	=	self::UPDATE;

		if ($this->tableExists($table)) {

			$this->baseTable	=	$table;
		}

		return $this;
	}

	private function addFields($table, $fields) {

		foreach ($fields as $f) {

			if ($table) $this->addFieldIndexedByTable($table, $f);
			else $this->addFieldIndexedByFieldName($f);
		}
	}

	private function addFieldIndexedByFieldName($field) {

		$this->validateField($field->field);
		$this->fields[$field->field] = $field;
	}

	private function addFieldIndexedByTable($table, $field) {

		$this->validateTable($table);
		$this->validateField($field->field);
		$this->fields[$table][] = $field;
	}

	private function establishConnection($password) {

		$dsn	=	'mysql:dbname=' . $this->database . ';';
		$dsn	.=	'host=' . $this->hostname;

		try {

			return new PDO($dsn, $this->username, $password);

		} catch (PDOException $e) {

			$message	=	__METHOD__ . ', PDO Error: ' . $e->getMessage();
			throw new Exception($message, 0, $e);
		}
	}

	private function fieldExists($field) {

		foreach ($this->schema as $table => $fields) {

			foreach ($fields as $f) {

				if ($f === $field) return true;
			}
		}

		$message = __METHOD__ . ': Given field not found in schema: ' .$field;
		throw new Exception($message);
	}

	private function loadSchema() {

		$schema		=	[];
		$q			=	"SELECT column_name, table_name FROM information_schema.columns
						WHERE table_schema='$this->database'";
		$pdoStmt	=	$this->conn->query($q);

		while ($row = $pdoStmt->fetch()) {

			$schema[$row['table_name']][] =	$row['column_name'];
		}
		return $schema;
	}

	private function resetQuery() {

		$this->baseTable	=	null;
		$this->conditions	=	[];
		$this->fields		=	[];
		$this->joins		=	[];
		$this->options		=	[];
		$this->orderBy		=	[];
		$this->queryType	=	null;
		$this->pdoStmt		=	null;
	}

	private function tableExists($table) {

		return (array_key_exists($table, $this->schema)) ? true : false;
	}

	/**
	 * @method Db::validatePassedFields()
	 * Validate an array of fields and confirm that they are instances of
	 * the QueryField class.
	 *
	 * @param array $fields
	 * The list of fields to check.
	 *
	 * @return void
	 * Returns void upon success. Throws an exception if any fields are found
	 * to be invalid.
	 */
	private function validateListOfFields(array $fields) {

		if (empty($fields)) {

			$m	=	__METHOD__ . ': Missing required option: \'fields\'.';
			throw new Exception($m);
		}
		else {

			foreach ($fields as $field) {

				if (!$field instanceof QueryField) {

					$m	=	__METHOD__ . ": Fields passed in 'field' option must
							be instances of QueryField.";
					throw new Exception($m);
				}
			}
		}
	}

	private function validateField($fieldName) {

		if (!$this->fieldExists($fieldName)) {

			$message	=	__METHOD__ . ': Invalid field given: ' . $table;
			throw new Exception($message);
		}
	}

	private function validateTable($table) {

		if (!$this->tableExists($table)) {

			$message	=	__METHOD__ . ': Invalid table given: ' . $table;
			throw new Exception($message);
		}
	}
}

