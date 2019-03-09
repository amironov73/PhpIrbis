<?php

require_once '../Source/PhpIrbis.php';

class FunctionsTest extends PHPUnit_Framework_TestCase
{
    public function testGetOne_1() {
        $first = 'First';
        $second = 'Second';
        $chosen = getOne($first, $second);
        $this->assertEquals($first, $chosen);

        $first = '';
        $chosen = getOne($first, $second);
        $this->assertEquals($second, $chosen);

        $first = null;
        $chosen = getOne($first, $second);
        $this->assertEquals($second, $chosen);
    }

    public function testGetOne_2() {
        $first = 'First';
        $second = 'Second';
        $third = 'Third';
        $chosen = getOne($first, $second, $third);
        $this->assertEquals($first, $chosen);

        $first = '';
        $chosen = getOne($first, $second, $third);
        $this->assertEquals($second, $chosen);

        $second = '';
        $chosen = getOne($first, $second, $third);
        $this->assertEquals($third, $chosen);

        $third = '';
        $chosen = getOne($first, $second, $third);
        $this->assertEquals($third, $chosen);
    }

    public function testSameString_1() {
        $first = 'Hello';
        $second = 'World';
        $this->assertFalse(sameString($first, $second));

        $first = 'Hello';
        $second = 'HELLO';
        $this->assertTrue(sameString($first, $second));

//        $first = 'привет';
//        $second = 'ПРИВЕТ';
//        $this->assertTrue(sameString($first, $second));
    }
}
