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
        $this->migrateMockModelsTable();
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
     * Create models for this test.
     *
     * @return void
     */
    private function migrateMockModelsTable()
    {
        DB::schema()->create('mock_model', function ($table) {
            $table->increments('id');
            $table->string('title', 255);
            $table->timestamps();
        });
    }

    /**
     * Generate a standard mock model for tests.
     *
     * @return MockModel
     */
    private function newModel()
    {
        $mock = new MockModel();
        $model = $mock->newFromBuilder(['title' => 'Test']);

        return $model;
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
     * Asset that `newFromBuilder` correctly sets up the model.
     */
    public function testNewFromBuilder()
    {
        $model = $this->newModel();
        $this->assertEquals($model->title, 'Test');
    }

    /**
     * Assert a number of simple searches.
     *
     * @return void
     */
    public function testSimpleSearchs()
    {
        $model = $this->newModel();

        // Wildcard by default.
        $query = MockModel::search(['title' => 'Test']);
        $this->assertEquals($this->getSql($query), 'select * from "mock_model" where ("mock_model"."title" LIKE \'%Test%\')');

        // Operator via array.
        $query = MockModel::search(['title' => [['=', 'Test']]]);
        $this->assertEquals($this->getSql($query), 'select * from "mock_model" where ("mock_model"."title" = \'Test\')');
    }

    /**
     * Assert a number of inline searches.
     *
     * @return void
     */
    public function testInlineOperatorSearchs()
    {
        // Force operator.
        $query = MockModel::search(['title' => '= Test']);
        $this->assertEquals($this->getSql($query), 'select * from "mock_model" where ("mock_model"."title" = \'Test\')');

        // Force operator (negative)
        $query = MockModel::search(['title' => '!= Test']);
        $this->assertEquals($this->getSql($query), 'select * from "mock_model" where ("mock_model"."title" != \'Test\')');
    }
}
