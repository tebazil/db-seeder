<?php
use tebazil\dbseeder\Generator;

/**
 * Created by PhpStorm.
 * User: tebazil
 * Date: 10.09.15
 * Time: 22:49
 */
class GeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @return mixed
     */
    public function getGenerator()
    {
        if(is_null($this->generator)) {
            $this->generator = new Generator(new \tebazil\dbseeder\Seeder(new Pdo('mysql:localhost','root','test'))); //yeah, I know we can benefit from some decoupling here
        }
        return $this->generator;
    }

    public function testPk()
    {
        /**
         * @var $generator Generator
         */
        $generator = $this->getGenerator();
        $this->assertEquals(1, $generator->getValue(Generator::PK, false));
        $this->assertEquals(2, $generator->getValue(Generator::PK, false));
        $generator->reset();
        $this->assertEquals(1, $generator->getValue(Generator::PK, false));
        $this->assertEquals(2, $generator->getValue([Generator::PK], false));
    }

    public function testAnonymousFunctions() {
        $generator = $this->getGenerator();
        $this->assertEquals([1,2,3], $generator->getValue(function() {return [1,2,3];}, false));
        $this->assertEquals(2, $generator->getValue(function() {return 2;}, false));
    }

    public function testFaker() {
        $generator = $this->getGenerator();
        /* property */
        $this->assertNotEmpty($generator->getValue([Generator::FAKER, 'name'], false));
        /* method with params */
        $this->assertEquals(10, strlen($generator->getValue([Generator::FAKER, 'password',[10,10]], false)));
        /* method with no params */
        $this->assertNotEmpty($generator->getValue([Generator::FAKER, 'password',[]], false));
        /* method as property */
        $this->assertNotEmpty($generator->getValue([Generator::FAKER, 'password'], false));
        /* empty options array defined */
        $this->assertNotEmpty($generator->getValue([Generator::FAKER, 'password', [],[]], false));
        /* optional */
        $values = [];
        for($i=0;$i<100;$i++) {
            $values[]= $generator->getValue([Generator::FAKER, 'password', [],['optional'=>[]]], false);
        }
        $this->assertTrue(in_array(null, $values), 'fakers optional() is working');

        //try with options

        /* unique */
        //try all options applied


    }

    public function testConstantValue() {
        $generator = $this->getGenerator();
        $value = 'input_equals_output';
        $this->assertEquals($value, $generator->getValue($value, false));
    }

    public function testRelations() {
        $generator = $this->getGenerator();
        $relatedColumnValues = [1,2,3,45,6,6,7, 8];
        $this->generator->setColumns('book', [
            'id'=> $relatedColumnValues,
        ]);
        for($i=0;$i<4;$i++) {
            $this->assertTrue(in_array($generator->getValue([Generator::RELATION, 'book','id']), $relatedColumnValues));
        }
    }


}
