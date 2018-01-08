<?php

namespace HnhDigital\ModelSearch\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    /**
     * PDO.
     *
     * @var
     */
    private $pdo;

    /**
     * Setup required for tests.
     *
     * @return void
     */
    public function setUp()
    {
        $this->configureDatabase();
    }

    /**
     * Configure database.
     *
     * @return void
     */
    private function configureDatabase()
    {
        $db = new DB();

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->pdo = DB::connection()->getPdo();
    }

    /**
     * Get binded sql.
     *
     * @param Builder $query
     *
     * @return string
     */
    private function getSql($query)
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        foreach ($bindings as $key => $binding) {
            $regex = is_numeric($key)
                ? "/\?(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/"
                : "/:{$key}(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";
            $sql = preg_replace($regex, $this->pdo->quote($binding), $sql, 1);
        }

        return $sql;
    }

    /**
     * Assert a number of simple searches.
     *
     * @return void
     */
    public function testSimpleSearchs()
    {
        $sql_begins_with = 'select * from "mock_model" where ';

        // Wildcard by default.
        $query = MockModel::search(['title' => 'Test']);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" LIKE \'%Test%\')', $this->getSql($query));

        // Operators via array.

        // String equal.
        $query = MockModel::search(['title' => [['=', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" = \'Test\')', $this->getSql($query));

        // String not equal.
        $query = MockModel::search(['title' => [['!=', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" != \'Test\')', $this->getSql($query));

        // String like bothside wildcard.
        $query = MockModel::search(['title' => [['*=*', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" LIKE \'%Test%\')', $this->getSql($query));

        // String like left wildcard.
        $query = MockModel::search(['title' => [['*=', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" LIKE \'%Test\')', $this->getSql($query));

        // String not like left wildcard.
        $query = MockModel::search(['title' => [['!*=', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" NOT LIKE \'%Test\')', $this->getSql($query));

        // String like right wildcard.
        $query = MockModel::search(['title' => [['=*', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" LIKE \'Test%\')', $this->getSql($query));

        // String not like right wildcard.
        $query = MockModel::search(['title' => [['!=*', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" NOT LIKE \'Test%\')', $this->getSql($query));

        // String in list (single).
        $query = MockModel::search(['title' => [['IN', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" in (\'Test\'))', $this->getSql($query));

        // String not in list (single).
        $query = MockModel::search(['title' => [['NOT_IN', 'Test']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" not in (\'Test\'))', $this->getSql($query));

        // String in list.
        $query = MockModel::search(['title' => [['IN', 'Test;Test1']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" in (\'Test\', \'Test1\'))', $this->getSql($query));

        // String not in list.
        $query = MockModel::search(['title' => [['NOT_IN', 'Test;Test1']]]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" not in (\'Test\', \'Test1\'))', $this->getSql($query));

        // String is null.
        $query = MockModel::search(['title' => 'NULL']);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" is null)', $this->getSql($query));

        // String is null.
        $query = MockModel::search(['title' => ['NULL']]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" is null)', $this->getSql($query));

        // String is not null.
        $query = MockModel::search(['title' => 'NOT_NULL']);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" is not null)', $this->getSql($query));

        // String is null.
        $query = MockModel::search(['title' => ['NOT_NULL']]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" is not null)', $this->getSql($query));

        // String is empty.
        $query = MockModel::search(['title' => 'EMPTY']);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" = \'\')', $this->getSql($query));

        // String is empty.
        $query = MockModel::search(['title' => ['EMPTY']]);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" = \'\')', $this->getSql($query));
    }

    /**
     * Assert a number of inline searches.
     *
     * @return void
     */
    public function testInlineOperatorSearchs()
    {
        $sql_begins_with = 'select * from "mock_model" where ';

        // Force operator.
        $query = MockModel::search(['title' => '= Test']);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" = \'Test\')', $this->getSql($query));

        // Force operator (negative)
        $query = MockModel::search(['title' => '!= Test']);
        $this->assertEquals($sql_begins_with.'("mock_model"."title" != \'Test\')', $this->getSql($query));
    }
}
