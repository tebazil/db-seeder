<?php

/**
 * Created by PhpStorm.
 * User: tebazil
 * Date: 10.09.15
 * Time: 22:14
 */
class DbHelperTest extends PHPUnit_Framework_TestCase
{
    private $pdo;
    private $dbHelper;

    /**
     * @return PDO
     */
    private function getPdo()
    {
        if (is_null($this->pdo)) {
            $this->pdo = new PDO('sqlite::memory:');
            $this->pdo->exec("CREATE TABLE asdf(
   ID INT PRIMARY KEY     NOT NULL,
   NAME           TEXT    NULL,
   COMMENT        TEXT     NULL
)");

            $this->pdo->exec("CREATE TABLE sffsdf(
   ID INT PRIMARY KEY     NOT NULL
)");

            $this->pdo->exec("CREATE TABLE asdfsdf(
   ID INT PRIMARY KEY     NOT NULL,
      NAME           TEXT    NULL
)");
            //throw new Exception(print_r($this->pdo->errorInfo(),true));
        }
        return $this->pdo;
    }

    private function getDbHelper() {
        if(is_null($this->dbHelper)) {
            $this->dbHelper = new \tebazil\dbseeder\DbHelper($this->getPdo());
        }
        return $this->dbHelper;
    }

    private function getTableNames()
    {
        return array_keys($this->getInsertValues());
    }

    private function getInsertValues()
    {

        return ['asdf' => ['id'=>'1','name'=>'objector','comment'=>'asdasdflasdjf'],
            'sffsdf' => ['id'=>2],
            'asdfsdf' => ['id'=>3, 'name'=>'jenn']
        ];
    }

    public function testTruncate()
    {
        $pdo = $this->getPdo();
        $helper = $this->getDbHelper();
        foreach($this->getInsertValues() as $table=>$values) {
            $result = $this->pdoFetchScalar("SELECT count(*) FROM $table"); //fetch scalar value correctly
            $this->assertFalse((bool)$result);

            $pdo->exec("INSERT INTO $table VALUES('".implode("','",$values)."')");
           $result = $this->pdoFetchScalar("SELECT count(*) FROM $table"); //fetch scalar value correctly
            //throw new Exception($result);
            $this->assertTrue((bool)$result);
            $helper->truncateTable($table);
            $result = $this->pdoFetchScalar("SELECT count(*) FROM $table"); //fetch scalar value correctly
            $this->assertFalse((bool)$result);
        }
    }

    public function testInsert() {
        $pdo = $this->getPdo();
        $helper = $this->getDbHelper();
        foreach($this->getInsertValues() as $table=>$values) {
            $pdo->exec("DELETE FROM $table");
            $result = $pdo->exec("SELECT count(*) FROM $table");
            $this->assertFalse((bool)$result);
            $helper->insert($table, $values);
            foreach($values as $column=>$value) {
                $result = $this->pdoFetchScalar("SELECT count(*) FROM $table WHERE $column='$value'");
                $this->assertTrue((bool)$result);
            }
        }
    }

    private function pdoFetchScalar($sql) {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
