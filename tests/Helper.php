<?php

class Helper
{
    public static function runPrivateMethod(&$object, $methodName, $args=[]) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    public static function getPrivateValue(&$object, $propertyName) {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);

    }

    public static function setPrivateValue(&$object, $propertyName, $value) {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public static function stdout($data)
    {
        fwrite(STDERR, print_r($data, true));
    }

}