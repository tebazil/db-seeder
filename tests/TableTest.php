<?php

use PHPUnit\Framework\TestCase;
use tebazil\dbseeder\Generator;

class TableTest extends TestCase
{
    const TABLE_NAME = 'table_name';
    private $testColumnConfig = [
        'id'=>[Generator::PK],
        'book_id'=>[Generator::RELATION, 'book','id'],
        'first_name' => ['faker', 'firstName'],
        'last_name' => ['faker', 'lastName'],
    ];

    private $testColumnConfigDependsOn = [
        'id',
        'book_id'=>[Generator::RELATION, 'book','id'],
        'id2'=>[Generator::PK],
        'other_id'=>[Generator::RELATION, 'other','other_dep_id'],
    ];

    private $rawDataWithKeys = [
        ['id' => 1, 'name' => 'jake', 'comment' => 'how'],
        ['id' => 2, 'name' => 'jak', 'comment' => 'hoow'],
        ['id' => 3, 'name' => 'sheila', 'comment' => 'hoopla'],
        ['id' => 4, 'name' => 'geeka', 'comment' => 'weeee'],
    ];
    private $rawDataWithoutKeys = [
        [1, 'jake', 'how'],
        [2, 'jak', 'hoow'],
        [3, 'sheila', 'hoopla'],
        [4, 'geeka', 'weeee'],
    ];

    private $rawDataInvalid = [
        'asdf'
    ];

    private $rawDataColumnNames = ['id', 'name', 'comment'];
    private $rawDataColumnNamesWithFalse = ['id', false, 'comment'];
    /** @var \tebazil\dbseeder\Table */
    private $table;

    private function arrayColumn($arr, $key)
    {
        $ret = [];
        foreach ($arr as $line) {
            if (!isset($line[$key])) {
                throw new InvalidArgumentException("Each array value should be an array and have the key specified");
            }
            $ret[] = $line[$key];
        }
        return $ret;
    }

    protected function setUp(): void
    {
        $this->table = new \tebazil\dbseeder\Table(self::TABLE_NAME, new Generator(), new \tebazil\dbseeder\DbHelper(new PDO('sqlite::memory:')));

    }

    public function testConstruct()
    {
        $this->assertEquals(self::TABLE_NAME, Helper::getPrivateValue($this->table, 'name'));
        $this->assertInstanceOf('tebazil\dbseeder\Generator', Helper::getPrivateValue($this->table, 'generator'));
        $this->assertInstanceOf('tebazil\dbseeder\DbHelper', Helper::getPrivateValue($this->table, 'dbHelper'));
    }

    public function testColumns()
    {
        $this->table->setColumns($this->testColumnConfig);
        $this->assertEquals($this->testColumnConfig, Helper::getPrivateValue($this->table, 'columnConfig'));
        $this->assertEquals(array_keys($this->testColumnConfig), array_keys(Helper::getPrivateValue($this->table, 'columns')));
    }

    public function testRowQuantityNonNumeric()
    {
        $this->expectException(Exception::class);
        $this->table->setRowQuantity('asdfas');
    }

    public function testRowQualityNumeric()
    {
        $value = 10;
        $this->table->setRowQuantity($value);
        $this->assertEquals($value, Helper::getPrivateValue($this->table, 'rowQuantity'));
    }

    public function testDataEmptyArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->setRawData([]);
    }

    public function testDataInvalidArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->setRawData($this->rawDataInvalid);

    }

    public function testDataWithoutKeysWithoutColumnNames()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->table->setRawData($this->rawDataWithoutKeys);
    }

    public function testDataWithKeys()
    {
        $this->table->setRawData($this->rawDataWithKeys);
        $this->assertEquals(array_keys(reset($this->rawDataWithKeys)), Helper::getPrivateValue($this->table, 'columnConfig'));
        $this->assertEquals(array_keys(reset($this->rawDataWithKeys)), array_keys(Helper::getPrivateValue($this->table, 'columns')));
        $this->assertEquals($this->rawDataWithKeys, Helper::getPrivateValue($this->table, 'rawData'));
    }

    public function testDataWithoutKeysWithColumnNames()
    {
        $this->table->setRawData($this->rawDataWithoutKeys, $this->rawDataColumnNames);
        $this->assertEquals($this->rawDataColumnNames, Helper::getPrivateValue($this->table, 'columnConfig'));
        $this->assertEquals($this->rawDataColumnNames, array_keys(Helper::getPrivateValue($this->table, 'columns')));
        $this->assertEquals($this->rawDataWithoutKeys, Helper::getPrivateValue($this->table, 'rawData'));
    }

    public function testCanBeFilled()
    {
        $filledTableNames = ['book', 'author', 'comments'];
        Helper::setPrivateValue($this->table, 'dependsOn', ['book']);
        $this->assertTrue($this->table->canBeFilled($filledTableNames));
        Helper::setPrivateValue($this->table, 'dependsOn', []);
        $this->assertTrue($this->table->canBeFilled($filledTableNames));
        Helper::setPrivateValue($this->table, 'dependsOn', ['users']);
        $this->assertFalse($this->table->canBeFilled($filledTableNames));
    }

    public function testFillFromRawDataWithKeys()
    {
        //this test is hardly readable
        $this->table->setRawData($this->rawDataWithKeys);
        $this->table->fill(false);
        $rows = $this->table->getRows();
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals($this->rawDataWithKeys[$i], $rows[$i]);
            $this->assertNotEquals($this->rawDataWithKeys[$i], $rows[$i + 1]);
        }
        $columns = $this->table->getColumns();
        $columns = array_values($columns);
        //repetition works
        $this->assertEquals([1, 2, 3, 4, 1, 2, 3], array_slice($columns[0], 0, 7));

        //test all columns
        array_walk($columns, function (&$value) {
            $value = array_slice($value, 0, 4);
        });
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'id'), $columns[0]);
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'name'), $columns[1]);
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'comment'), $columns[2]);
    }

    public function testFillFromRawDataWithoutKeysWithColumnConfig()
    {
        //this test is hardly readable also
        $this->table->setRawData($this->rawDataWithoutKeys, $this->rawDataColumnNames);
        $this->table->fill(false);
        $rows = $this->table->getRows();
        for ($i = 0; $i < 4; $i++) {
            $this->assertEquals($this->rawDataWithKeys[$i], $rows[$i]);
            $this->assertNotEquals($this->rawDataWithKeys[$i], $rows[$i + 1]);
        }
        $columns = $this->table->getColumns();
        $columns = array_values($columns);

        //test all columns
        array_walk($columns, function (&$value) {
            $value = array_slice($value, 0, 4);
        });
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'id'), $columns[0]);
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'name'), $columns[1]);
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'comment'), $columns[2]);
    }

public function testFillFromRawDataWithoutKeysWithColumnConfigOneIsFalse()
    {
        $columnNames = $this->rawDataColumnNames;
        $columnNames[1] = false;
        $this->table->setRawData($this->rawDataWithoutKeys, $columnNames);
        $this->table->fill(false);
        $rows = $this->table->getRows();
        for ($i = 0; $i < 4; $i++) {
            $copy = $this->rawDataWithKeys[$i];
            unset($copy[array_keys($copy)[1]]);
            $this->assertEquals($copy, $rows[$i]);
            $this->assertNotEquals($copy, $rows[$i + 1]);
        }
        $columns = $this->table->getColumns();
        $columns = array_values($columns);
        //repetition works
        $this->assertEquals([1, 2, 3, 4, 1, 2, 3], array_slice($columns[0], 0, 7));

        //test all columns
        array_walk($columns, function (&$value) {
            $value = array_slice($value, 0, 4);
        });
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'id'), $columns[0]);
        //column with names is skipped
        $this->assertEquals($this->arrayColumn($this->rawDataWithKeys, 'comment'), $columns[1]);
    }

    public function testFillFromGenerators()
    {
        $columns = [
            'id',
            'name'=>[Generator::FAKER, 'firstName'],
            'comment'=>[Generator::FAKER, 'paragraph'],
        ];
        $this->table->setColumns($columns)->fill(false);
        foreach($this->table->getRows() as $row) {
            $this->assertEquals($row, array_filter($row));
        }

        foreach($this->table->getColumns() as $column) {
            $this->assertEquals($column, array_filter($column));
        }
    }

    public function testCalcDependsOnRawDataSet()
    {
        $this->table->setRawData($this->rawDataWithKeys);
        $this->assertEquals([], $this->table->getDependsOn());
    }

    public function testCalcDependsOnNoDependency()
    {
        $columnConfig = $this->testColumnConfig;
        unset($columnConfig['book_id']);
        $this->table->setColumns($columnConfig);
        $this->assertEquals([], $this->table->getDependsOn());
    }

    public function testCalcDependsOnHasDependencies()
    {
        $this->table->setColumns($this->testColumnConfig);
        $this->assertEquals(['book'], $this->table->getDependsOn());
    }

    public function testCalcDependsOnHasDependencies2() {
        $this->table->setColumns($this->testColumnConfigDependsOn);
        $this->assertEquals(['book','other'], $this->table->getDependsOn());
    }
}
