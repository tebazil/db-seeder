<?php

/**
 * Created by PhpStorm.
 * User: tebazil
 * Date: 17.09.15
 * Time: 16:58
 */
class GeneratorConfiguratorTest extends PHPUnit_Framework_TestCase
{
    private $configurator;
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->configurator = new \tebazil\dbseeder\GeneratorConfigurator();
        parent::__construct($name, $data, $dataName);
    }


    public function testPk() {
        $this->assertEquals('pk', $this->configurator->pk());
    }

    public function testRelation() {
        $table = 'table';
        $column = 'column';
        $this->assertEquals(['relation',$table, $column], $this->configurator->relation($table, $column));
    }

    public function testGetFakerConfigurator() {
        $this->assertInstanceOf('tebazil\dbseeder\FakerConfigurator', $this->configurator->getFakerConfigurator());
    }

}
