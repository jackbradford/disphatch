<?php
/**
 * @file IQueryPreparer.php
 * This file provides an interface for query preparers.
 *
 */
namespace JackBradford\ActionRouter\Db;

interface IQueryPreparer {

    public function prepareDeleteStmt($options);
    public function prepareInsertStmt($options);
    public function prepareSelectStmt($options);
    public function prepareUpdateStmt($options);
}

