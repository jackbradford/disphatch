<?php
/**
 * @file query_preparer.php
 * This file provides the QueryPreparer class.
 *
 * @class MySqlQueryPreparer
 * This class is responsible for preparing mySQL queries.
 *
 */
namespace JackBradford\ActionRouter\Db;

class MySqlQueryPreparer implements IQueryPreparer {

	private $params = [];

	/**
	 * @method QueryPreparer::prepareDeleteStmt()
	 * Prepare a mySQL DELETE statement.
	 *
	 * @param obj $options
	 * An object containing the specifications of the query.
	 *
	 * @param str $options->baseTable
	 * The table from which to delete records.
	 *
	 * @param array $options->conditions
	 * The list of conditions to apply to the query.
	 *
	 * @return PreparedStatement
	 */
	public function prepareDeleteStmt($options) {

		$baseTable	=	$options->baseTable;
		$conditions	=	$options->conditions;
		$this->resetParams();

		$query		=	'DELETE FROM ' . $baseTable;

		if (count($conditions) > 0) {

			$query .= ' WHERE '.$this->conditionsToQuerySubstring($conditions);
		}

		return new PreparedStatement($query, $this->params);
	}

	/**
	 * @method QueryPreparer::prepareInsertStmt()
	 * Prepare a mySQL INSERT statement.
	 *
	 * @param obj $options
	 * An object containing the specifications of the query.
	 *
	 * @param str $options->baseTable
	 * The table in which to insert record(s).
	 *
	 * @param array $options->fields
	 * The fields, and thier data, to add to the record(s).
	 *
	 * @return PreparedStatement
	 */
	public function prepareInsertStmt($options) {
		
		$this->resetParams();

		$query	=	'INSERT INTO ' . $options->baseTable
				.	$this->createFieldsClauseForInsertQuery($options->fields)
				.	$this->createValuesClauseForInsertQuery($options->fields);

		return new PreparedStatement($query, $this->params);
	}

	/**
	 * @method QueryPreparer::prepareSelectStmt()
	 * Prepare a mySQL SELECT statement.
	 *
	 * @param obj $options
	 * An object containing the specifications of the query.
	 *
	 * @param str $options->baseTable
	 * The table from which to select record(s).
	 *
	 * @param array $options->fields
	 * The fields, and their aliases, to select.
	 *
	 * @param array $options->joins
	 * A list of objects which represent tables to be joined.
	 *
	 * @param array $options->conditions
	 * A list of objects which represent conditions to apply to the query.
	 *
	 * @param array $options->orderBy
	 * A list of objects which represent sort directives.
	 *
	 * @return PreparedStatement
	 */
	public function prepareSelectStmt($options) {

		$this->resetParams();

		$query	=	'SELECT '
				.	$this->createFieldsClauseForSelectQuery($options->fields)
				.	' FROM ' . $options->baseTable . ' '
				.	$this->createJoinClauseForSelectQuery($options->joins)
				.	$this->createWhereClauseForSelectQuery($options->conditions)
				.	$this->createOrderByClauseForSelectQuery($options->orderBy);

		return new PreparedStatement($query, $this->params);
	}

	/**
	 * @method QueryPreparer::prepareUpdateStmt()
	 * Prepare a mySQL UPDATE statement.
	 *
	 * @param obj $options
	 * An object containing the specifications of the query.
	 *
	 * @param str $options->baseTable
	 * The table in which to update record(s).
	 *
	 * @param array $options->fields
	 * The fields, and their values, to update.
	 *
	 * @param array $options->conditions
	 * A list of objects which represent conditions to apply to the query.
	 *
	 * @return PreparedStatement
	 */
	public function prepareUpdateStmt($options) {

		$this->resetParams();
		$query	=	'UPDATE ' . $options->baseTable . ' SET ';

		// Add fields/values to query string.
		foreach ($options->fields as $field => $value) {

			$query	.=	"$field = $value, ";
		}
		$query	=	substr($query, 0, -2); // Removes trailing comma.

		// Add conditions to query string.
		if (count($options->conditions) > 0) {

			$query	.=	' WHERE ';
			$query	.=	$this->conditionsToQuerySubstring();
		}

		return new PreparedStatement($query, $this->params);
	}

	private function conditionsToQuerySubstring($conditions) {

		$q_substr	=	'';

		foreach ($conditions as $c) {

			$q_substr		.=	"$c->conjunction $c->field $c->operator ? ";

			if (is_array($c->value)) {

				$m	=	__METHOD__ . ' Arrays are not valid values for conditions.';
				throw new Exception($m);
			}

			$this->params[]	=	$c->value;
		}
		return $q_substr;
	}

	private function createFieldsClauseForInsertQuery($fields) {

		$query	=	' (';
		foreach ($fields as $field) {

			$query	.=	$field->field . ', ';
		}
		return substr($query, 0, -2) . ') ';
	}

	private function createFieldsClauseForSelectQuery($fields) {

		$query = '';

		foreach ($fields as $table => $fields) {

			foreach ($fields as $field) {

				$query	.=	"$table.$field->field ";
				if ($field->hasAlias()) {

					$query	.=	"as $field->alias";
				}
				$query	.=	', ';
			}
		}
		return substr($query, 0, -2);
	}

	private function createJoinClauseForSelectQuery($joins) {

		$query = '';
		foreach ($joins as $join) {

			$query	.=	"$join->type JOIN $join->table ON " .
						"$join->on $join->operator $join->compareTo ";
		}
		return $query;
	}

	private function createOrderByClauseForSelectQuery($orderBy) {

		if (count($orderBy) > 0) {

			$query	=	'ORDER BY ';
			foreach ($orderBy as $o) {

				$query	.=	"$o->field $o->direction, ";
			}
			$query	=	substr($query, 0, -2); // Removes trailing comma.
		}
		return (isset($query)) ? $query : '';
	}

	private function createValuesClauseForInsertQuery($fields) {

		$query	=	'VALUES (';
		foreach ($fields as $field) {

			$query	.=	'?,';
			$this->params[]	=	$field->value;
		}
		return substr($query, 0, -1) . ') ';
	}

	private function createWhereClauseForSelectQuery($conditions) {

		$query	=	'';
		if (count($conditions) > 0) {

			$query	.=	'WHERE '
					.	$this->conditionsToQuerySubstring($conditions);
		}
		return $query;
	}

	private function resetParams() {

		$this->params	=	[];
	}
}

