<?php

require_once '../Source/PhpIrbis.php';

class RecordFieldTest extends PHPUnit_Framework_TestCase {

    public function testClone_1() {
        $first = new RecordField(300, 'Hello');
        $second = clone $first;
        $this->assertEquals($first->tag, $second->tag);
        $this->assertEquals($first->value, $second->value);
        $this->assertEquals(count($first->subfields), count($second->subfields));
    }

    public function testClone_2() {
        $first = new RecordField();
        $second = clone $first;
        $this->assertEquals($first->tag, $second->tag);
        $this->assertEquals($first->value, $second->value);
        $this->assertEquals(count($first->subfields), count($second->subfields));
    }

    public function testAdd_1() {
        $field = new RecordField(200);
        $field->add('a', 'SubA');
        $this->assertEquals(1, count($field->subfields));
    }

    public function testClear_1() {
        $field = new RecordField(200);
        $field->add('a', 'SubA');
        $field->clear();
        $this->assertEquals($field->value, '');
        $this->assertEquals(0, count($field->subfields));
    }

    function getField_1() {
        $field = new RecordField(461);
        $field->add('1', '2001#');
        $field->add('a', 'Златая цепь');
        $field->add('e', 'Записки. Повести. Рассказы');
        $field->add('f', 'Бондарин С. А.');
        $field->add('v', 'С. 76-132');
        return $field;
    }

    function getField_2() {
        $field = new RecordField(461);
        $field->add('1', '2001#');
        $field->add('a', 'Златая цепь');
        $field->add('e', 'Записки. Повести. Рассказы');
        $field->add('f', 'Бондарин С. А.');
        $field->add('v', 'С. 76-132');
        $field->add('1', '2001#');
        $field->add('a', 'Руслан и Людмила');
        $field->add('f', 'Пушкин А. С.');
        return $field;
    }

    public function testGetEmbeddedFields_1() {
        $field = new RecordField(200);
        $embedded = $field->getEmbeddedFields();
        $this->assertEquals(0, count($embedded));
    }

    public function testGetEmbeddedFields_2() {
        $field = $this->getField_1();
        $embedded = $field->getEmbeddedFields();
        $this->assertEquals(1, count($embedded));
        $this->assertEquals(200, $embedded[0]->tag);
        $this->assertEquals(4, count($embedded[0]->subfields));
        $this->assertEquals('a', $embedded[0]->subfields[0]->code);
        $this->assertEquals('Златая цепь', $embedded[0]->subfields[0]->value);
    }

    public function testGetEmbeddedFields_3() {
        $field = $this->getField_2();
        $embedded = $field->getEmbeddedFields();
        $this->assertEquals(2, count($embedded));
        $this->assertEquals(200, $embedded[0]->tag);
        $this->assertEquals(200, $embedded[1]->tag);
        $this->assertEquals(4, count($embedded[0]->subfields));
        $this->assertEquals('a', $embedded[0]->subfields[0]->code);
        $this->assertEquals('Златая цепь', $embedded[0]->subfields[0]->value);
        $this->assertEquals(2, count($embedded[1]->subfields));
        $this->assertEquals('a', $embedded[1]->subfields[0]->code);
        $this->assertEquals('Руслан и Людмила', $embedded[1]->subfields[0]->value);
    }
}
