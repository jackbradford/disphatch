<?php
/**
 * @file prepared_stmt.php
 * This file provides the PreparedStatment class.
 *
 * @class PreparedStatement
 * This class is responsible for representing a prepared statement and the
 * parameters which should be bound to it.
 *
 */
namespace JackBradford\ActionRouter\Db;

class PreparedStatement {

    private $params, $query;

    /**
     * @method PreparedStatement::__construct()
     * Create a representation of a prepared statement.
     *
     * @param str $query
     * The prepared query string.
     *
     * @param array $params
     * The set of parameters, ordered as they appear in the query.
     *
     * @return PreparedStatment
     */
    public function __construct($query, array $params) {

        try {

            $this->validateQuery($query);
            $this->query = $query;
            $this->params = $params;
        }
        catch (Exception $e) {

            $m = __METHOD__ . ' error: ' . $e->getMessage();
            throw new Exception($m, 0, $e);
        }
    }

    /**
     * @method PreparedStatement::getParams()
     * Get the set of params.
     *
     * @return array
     */
    public function getParams() {

        return $this->params;
    }

    /**
     * @method PreparedStatement::getQuery()
     * Get the query string.
     *
     * @return str
     */
    public function getQuery() {

        return $this->query;
    }

    private function validateQuery($query) {

        if (!is_string($query)) {

            $m = __METHOD__ .': Query must be a string.';
            throw new Exception ($m);
        }
        return true;
    }
}

