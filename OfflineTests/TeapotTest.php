<?php

require_once '../Source/Teapot.php';

class TeapotTest extends PHPUnit_Framework_TestCase
{
    public function testBuildSearchExpression_1()
    {
        $teapot = new Irbis\Teapot();
        $expected = '';
        $actual = $teapot->buildSearchExpression('concrete');
        $this->assertEquals($expected, $actual);
    }
}