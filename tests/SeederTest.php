<?php

/**
 * Created by PhpStorm.
 * User: tebazil
 * Date: 19.09.15
 * Time: 22:34
 */
class SeederTest extends PHPUnit_Framework_TestCase
{
    /** @var \tebazil\dbseeder\Seeder */
    private $seeder;
    private $_pdo;

    public function setUp()
    {
        $this->seeder = new \tebazil\dbseeder\Seeder($this->getPdo());
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('tebazil\dbseeder\GeneratorConfigurator', Helper::getPrivateValue($this->seeder, 'generatorConfigurator'));
        $this->assertInstanceOf('tebazil\dbseeder\DbHelper', Helper::getPrivateValue($this->seeder, 'dbHelper'));
        $this->assertInstanceOf('tebazil\dbseeder\Generator', Helper::getPrivateValue($this->seeder, 'generator'));
    }

    public function testTable()
    {
        $ret = $this->seeder->table('abc');
        $this->assertInstanceOf('tebazil\dbseeder\TableConfigurator', $ret);
        $this->assertInstanceOf('tebazil\dbseeder\Table', Helper::getPrivateValue($this->seeder, 'tables')['abc']);
    }

    public function testCheckCrossDependentTablesPresent()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->seeder->table('book')->columns([
            'article_id'
        ]);
        $this->seeder->table('article')->columns([
            'book_id'
        ]);
        Helper::runPrivateMethod($this->seeder, 'checkCrossDependentTables');
    }

    public function testCheckCrossDependentTablesNotPresent()
    {
        $this->seeder->table('book')->columns([
            'id'
        ]);
        $this->seeder->table('article')->columns([
            'id'
        ]);
        Helper::runPrivateMethod($this->seeder, 'checkCrossDependentTables');
    }


    public function testRefill()
    {
        //create tables
        $pdo = $this->getPdo();
        $pdo->exec("
        CREATE TABLE article(
   id INT PRIMARY KEY     NOT NULL,
   author_id           INT    NULL,
   title        TEXT     NULL,
   url        TEXT     NULL,
   content        TEXT     NULL,
   status        TEXT     NULL
)");
        $pdo->exec("
        CREATE TABLE user(
   id INT PRIMARY KEY     NOT NULL,
   parent_id           INT    NULL,
   profile_id           INT    NULL,
   username        TEXT     NULL,
   password_hash        TEXT     NULL
)");

        $pdo->exec("
        CREATE TABLE profile(
   id INT PRIMARY KEY     NOT NULL,
   first_name        TEXT     NULL,
   last_name        TEXT     NULL,
   is_active           INT    NULL,
   planet TEXT NULL
)");

        //set up seeder
        $seeder = $this->seeder;
        $generator = $seeder->getGeneratorConfigurator();
        $faker = $generator->getFakerConfigurator();
        $articleColumns = [
            'id',
            'author_id' => $generator->relation('user', 'id'),
            'title' => $faker->text(30),
            'url' => $faker->url,
            'content' => $faker->optional()->text(400),
            'status' => $faker->randomElement([0, 1, 2]),
        ];
        $seeder->table('article')->columns($articleColumns)->rowQuantity(40);
        $userColumns = [
            'id',
            'parent_id'=>$generator->relation('user','id'),
            'profile_id',
            'username' => $faker->userName,
            'password_hash' => $faker->md5,
        ];
        $seeder->table('user')->columns($userColumns)->rowQuantity(10);
        $profileColumns = [
            'id',
            'first_name' => $faker->firstName,
            'last_name' => $faker->lastName,
            'is_active' => function () {
                return rand(0, 1);
            },
            'planet' => 'earth',
        ];
        $seeder->table('profile')->columns($profileColumns)->rowQuantity(10);

        $seeder->refill();

        $checkDbValues = function($tableName, $columns, $expectedRowQuantity, $excludeColumns=[]) {
            $data = $this->pdoFetchAll("SELECT * FROM ".$tableName);
            $this->assertEquals($expectedRowQuantity, sizeof($data));
            foreach($data as $row) {
                foreach ($columns as $key => $value) {
                    $column = is_numeric($key) ? $value : $key;
                    if (!in_array($column, $excludeColumns)) {
                        $this->assertNotNull($row[$column]);
                }
                }
            }
        };

    $checkDbValues("article", $articleColumns, 40, ['content']);
    $checkDbValues("user", $userColumns, 10);
    $checkDbValues("profile", $profileColumns, 10);



    }

    private function pdoFetchScalar($sql) {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function pdoFetchAll($sql) {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getPdo()
    {
        if(is_null($this->_pdo)) {
            $this->_pdo = new PDO('sqlite::memory:');
        }
        return $this->_pdo;
    }

}
