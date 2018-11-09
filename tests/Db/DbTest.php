<?php
/**
 * @file DbTest.php
 * This file provides unit tests for the Db class.
 *
 */
use PHPUnit\Framework\TestCase;
use JackBradford\ActionRouter\Config\Config;
use JackBradford\ActionRouter\Db\Db;
use JackBradford\ActionRouter\Db\QueryPreparer;

final class DbTest extends TestCase {

    private $config;
    private $db;

    protected function setUp() {

        $confFile = '/var/www/vhosts/action-router/src/Config/actionrouter.conf.json';
        $this->config = new Config();
        $this->config->load($confFile);
        $db = $this->config->getDirective('dbs')->test;

        $this->db = new Db(

            $db->hostname,
            $db->password,
            $db->database,
            $db->username,
            new QueryPreparer()
        );

        $this->setupTestDb();
    }

    protected function tearDown() {

        $this->config = null;
        $this->db = null;
    }

    protected function setupTestDb() {

        $dbConf = $this->config->getDirective('dbs')->test;
        $dsn = 'mysql:dbname='.$dbConf->database.';host='.$dbconf->hostname;
        $db = new PDO($dsn, $dbConf->username, $dbConf->password);

        $db->query("DROP TABLE IF EXISTS 'test_table'");
        $db->query(
            'CREATE TABLE test_table (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                test_field VARCHAR(20),
                PRIMARY KEY (id)
            ) ENGINE=INNODB'
        );
        $db->query("INSERT INTO test_table (test_field) VALUES ('test_value')");
        $db->query("INSERT INTO test_table (test_field) VALUES ('test_value_two')");
        $db->query("INSERT INTO test_table (test_field) VALUES ('test_value_three')");
    }

    /**
     * @expectedException PHPUnit\Framework\Error\Error
     */
    public function testCannotBeCreatedFromInvalidCredentials() {

        $testDb = $this->config->getDirective('dbs')->test;
        $db = new Db(
            $testDb->hostname,
            'x',
            $testDb->database,
            $testDb->username,
            new QueryPreparer()
        );
    }

    /**
     * @method DbTest::testSelectWithConditions
     * Test the outcome of SELECT queries which feature conditions.
     *
     */
    public function testSelectWithConditions() {

        $results = $this->db->select('test_table')
            ->fields([
                'table' => 'test_table',
                'fields' => [
                    new QueryField('id'),
                    new QueryField('test_field'),
                ]
            ])
            ->condition('test_field', '=', 'test_value')
            ->condition('test_field', '=', 'test_value_two', 'OR')
            ->execute()
            ->fetchAllResults();

        $this->assertCount(2, $results);
        $this->assertContains('test_field', $results[0]);
        $this->assertContains('test_field', $results[1]);
        $this->assertEquals('test_value', $results[0]['test_field']);
        $this->assertEquals('test_value_two', $results[1]['test_field']);
    }

    public function testSecondConditionCannotBeAddedWithoutConjunction() {

        $this->expectException(InvalidArgumentException::class);
        $this->db->select('test_table')
            ->fields([
                'table' => 'test_table',
                'fields' => [
                    new QueryField('id'),
                    new QueryField('test_field'),
                ]
            ])
            ->condition('test_field', '=', 'test_value')
            ->condition('test_field', '=', 'test_value_two')
            ->execute();
    }

    public function testConditionValueArgumentCannotBeArray() {

        $this->expectException(InvalidArgumentException::class);
        $this->db->select('test_table')
            ->fields([
                'table' => 'test_table',
                'fields' => [
                    new QueryField('id'),
                    new QueryField('test_field'),
                ]
            ])
            ->condition('test_field', '=', ['test_value'])
            ->execute();
    }

    public function testCanSelectWithJoins() {

    }

    public function testCannotInitDeleteWithoutValidTableName() {

    }
}

