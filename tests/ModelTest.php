<?php

namespace HnhDigital\ModelSearch\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    /**
     * SQL begins with.
     *
     * @var string
     */
    private $sql_begins_with = 'select * from "mock_model" where ';

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
     * Assert a number of simple string searches.
     *
     * @return void
     */
    public function testSimpleStringSearches()
    {
        // Wildcard by default.
        $query = MockModel::search(['title' => 'Test']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" like \'%Test%\')', $this->getSql($query));

        // Wildcard by default - using array of values.
        $query = MockModel::search(['title' => ['Test']]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" like \'%Test%\')', $this->getSql($query));

        // Wildcard by default - using array of values.
        $query = MockModel::search(['title' => ['Test', 'Test1']]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" like \'%Test%\') and ("mock_model"."title" like \'%Test1%\')', $this->getSql($query));

        // Wildcard by default - using array of values.
        $query = MockModel::search(['title' => [['Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" like \'%Test%\')', $this->getSql($query));

        // Operators via array.

        // String equal.
        $query = MockModel::search(['title' => [['=', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" = \'Test\')', $this->getSql($query));

        // String not equal.
        $query = MockModel::search(['title' => [['!=', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" != \'Test\')', $this->getSql($query));

        // String like bothside wildcard.
        $query = MockModel::search(['title' => [['*=*', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" like \'%Test%\')', $this->getSql($query));

        // String like bothside wildcard.
        $query = MockModel::search(['title' => [['*!=*', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" not like \'%Test%\')', $this->getSql($query));

        // String like left wildcard.
        $query = MockModel::search(['title' => [['*=', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" like \'%Test\')', $this->getSql($query));

        // String not like left wildcard.
        $query = MockModel::search(['title' => [['*!=', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" not like \'%Test\')', $this->getSql($query));

        // String like right wildcard.
        $query = MockModel::search(['title' => [['=*', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" like \'Test%\')', $this->getSql($query));

        // String not like right wildcard.
        $query = MockModel::search(['title' => [['!=*', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" not like \'Test%\')', $this->getSql($query));

        // String in list (single).
        $query = MockModel::search(['title' => [['IN', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" in (\'Test\'))', $this->getSql($query));

        // String not in list (single).
        $query = MockModel::search(['title' => [['NOT_IN', 'Test']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" not in (\'Test\'))', $this->getSql($query));

        // String in list.
        $query = MockModel::search(['title' => [['IN', 'Test;Test1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" in (\'Test\', \'Test1\'))', $this->getSql($query));

        // String not in list.
        $query = MockModel::search(['title' => [['NOT_IN', 'Test;Test1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" not in (\'Test\', \'Test1\'))', $this->getSql($query));

        // String is null.
        $query = MockModel::search(['title' => 'NULL']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" is null)', $this->getSql($query));

        // String is null.
        $query = MockModel::search(['title' => ['NULL']]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" is null)', $this->getSql($query));

        // String is not null.
        $query = MockModel::search(['title' => 'NOT_NULL']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" is not null)', $this->getSql($query));

        // String is null.
        $query = MockModel::search(['title' => ['NOT_NULL']]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" is not null)', $this->getSql($query));

        // String is empty.
        $query = MockModel::search(['title' => 'EMPTY']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" = \'\')', $this->getSql($query));

        // String is empty.
        $query = MockModel::search(['title' => ['NOT_EMPTY']]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" != \'\')', $this->getSql($query));

        // Search for a "phone number"
        $query = MockModel::search(['phone' => '1234 56 789']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."phone" like \'%1%2%3%4%5%6%7%8%9%\')', $this->getSql($query));

        // Search for a "phone number"
        $query = MockModel::search(['phone' => ['1234 56 789']]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."phone" like \'%1%2%3%4%5%6%7%8%9%\')', $this->getSql($query));

        // Search for a "phone number", convert any operator to wild card search.
        $query = MockModel::search(['phone' => [['=', '1234 56 789']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."phone" like \'%1%2%3%4%5%6%7%8%9%\')', $this->getSql($query));

        // Search for a "phone number", convert any operator to wild card search.
        $query = MockModel::search(['phone' => [['!=', '1234 56 789']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."phone" not like \'%1%2%3%4%5%6%7%8%9%\')', $this->getSql($query));
    }

    /**
     * Assert a number of inline searches.
     *
     * @return void
     */
    public function testInlineOperatorSearches()
    {
        // Force operator.
        $query = MockModel::search(['title' => '= Test']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" = \'Test\')', $this->getSql($query));

        // Force operator (negative)
        $query = MockModel::search(['title' => '!= Test']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."title" != \'Test\')', $this->getSql($query));
    }

    /**
     * Assert a number of simple number searches.
     *
     * @return void
     */
    public function testSimpleNumberSearches()
    {
        // Equal by default.
        $query = MockModel::search(['total' => '1']);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" = \'1\')', $this->getSql($query));

        // Number is equal.
        $query = MockModel::search(['total' => [['=', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" = \'1\')', $this->getSql($query));

        // Number is not equal.
        $query = MockModel::search(['total' => [['!=', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" != \'1\')', $this->getSql($query));

        // Number is greater than
        $query = MockModel::search(['total' => [['>', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" > \'1\')', $this->getSql($query));

        // Number is greater than or equal.
        $query = MockModel::search(['total' => [['>=', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" >= \'1\')', $this->getSql($query));

        // Number is less than.
        $query = MockModel::search(['total' => [['<', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" < \'1\')', $this->getSql($query));

        // Number is less than or equal.
        $query = MockModel::search(['total' => [['<=', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" <= \'1\')', $this->getSql($query));

        // Number is in a list.
        $query = MockModel::search(['total' => [['IN', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" in (\'1\'))', $this->getSql($query));

        // Number is in a list.
        $query = MockModel::search(['total' => [['IN', '1;2']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" in (\'1\', \'2\'))', $this->getSql($query));

        // Number is in a list.
        $query = MockModel::search(['total' => [['IN', [1,2]]]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" in (\'1\', \'2\'))', $this->getSql($query));

        // Number is not in a list.
        $query = MockModel::search(['total' => [['NOT_IN', '1']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" not in (\'1\'))', $this->getSql($query));

        // Number is not in a list.
        $query = MockModel::search(['total' => [['NOT_IN', '1;2']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" not in (\'1\', \'2\'))', $this->getSql($query));

        // Number has no value.
        $query = MockModel::search(['total' => [['EMPTY']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" = \'\')', $this->getSql($query));

        // Number has value.
        $query = MockModel::search(['total' => [['NOT_EMPTY']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" != \'\')', $this->getSql($query));

        // Number is null.
        $query = MockModel::search(['total' => [['NULL']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" is null)', $this->getSql($query));

        // Number is not null.
        $query = MockModel::search(['total' => [['NOT_NULL']]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."total" is not null)', $this->getSql($query));
    }

    /**
     * Assert a number of simple boolean searches.
     *
     * @return void
     */
    public function testSimpleBooleanSearches()
    {
        // Is true.
        $query = MockModel::search(['is_enabled' => true]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" = \'1\')', $this->getSql($query));

        // Is true.
        $query = MockModel::search(['is_enabled' => [true]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" = \'1\')', $this->getSql($query));

        // Is false.
        $query = MockModel::search(['is_enabled' => false]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" = \'0\')', $this->getSql($query));

       // Is false.
        $query = MockModel::search(['is_enabled' => [false]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" = \'0\')', $this->getSql($query));

        // Is true.
        $query = MockModel::search(['is_enabled' => [['=', true]]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" = \'1\')', $this->getSql($query));

        // Is not true.
        $query = MockModel::search(['is_enabled' => [['!=', true]]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" != \'1\')', $this->getSql($query));

        // Is false.
        $query = MockModel::search(['is_enabled' => [['=', false]]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" = \'0\')', $this->getSql($query));

        // Is not false.
        $query = MockModel::search(['is_enabled' => [['!=', false]]]);
        $this->assertEquals($this->sql_begins_with.'("mock_model"."is_enabled" != \'0\')', $this->getSql($query));
    }
}
