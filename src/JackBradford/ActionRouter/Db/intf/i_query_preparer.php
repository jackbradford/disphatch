<?php
/**
 * @file i_query_preparer.php
 * This file provides an interface for query preparers.
 *
 */
interface IQueryPreparer {

	public function prepareDeleteStmt($options);
	public function prepareInsertStmt($options);
	public function prepareSelectStmt($options);
	public function prepareUpdateStmt($options);
}

