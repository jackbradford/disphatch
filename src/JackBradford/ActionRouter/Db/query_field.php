<?php
/**
 * @file query_field.php
 * This file provides the QueryField class.
 *
 * @class QueryField
 * This class is responsible for representing the specifications of a field
 * in a database query. 
 *
 * Such an entity will always include the name of the field as it appears in
 * the database. The context in which this class is used (i.e., the type of
 * query it is used with) will determine whether the instance should include
 * an alias and/or a value.
 *
 */
class QueryField {

	public $field, $alias, $value;

	/**
	 * @method QueryField::__construct()
	 * Create a new instance of QueryField to represent a field in a
	 * query.
	 *
	 * @param str $field
	 * The name of the field as it appears in the database.
	 *
	 * @param str $alias
	 * The alias to assign the field. This will affect e.g. the columns
	 * returned from a SELECT statement.
	 *
	 * @param mixed $value
	 * The value to assign the field. This is necessary for e.g. INSERT
	 * and UPDATE statements.
	 *
	 * @return QueryField
	 */
	public function __construct($field, $alias = null, $value = null) {

		$this->field	=	$field;
		$this->alias	=	$alias;
		$this->value	=	$value;
	}

	/**
	 * @method QueryField::hasAlias()
	 * Check whether the field was assigned an alias.
	 *
	 * @return bool
	 */
	public function hasAlias() {

		return ($this->alias === null) ? false : true;
	}

	/**
	 * @method QueryField::hasValue()
	 * Check whether the field was assigned a value.
	 *
	 * @return bool
	 * The alias is initialized with a null value. If the value is null,
	 * returns false.
	 */
	public function hasValue() {

		return ($this->value === null) ? false : true;
	}
}

