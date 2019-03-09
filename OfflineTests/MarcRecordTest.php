<?php

require_once '../Source/PhpIrbis.php';

class MarcRecordTest extends PHPUnit_Framework_TestCase
{
    public function testFm_1()
    {
        $record = new MarcRecord();
        $record->add(100, "Field100");
        $record->add(200)
            ->add('a', "Заглавие")
            ->add('e', "Подзаголовочные сведения");
        $this->assertEquals(2, count($record->fields));

        $found = $record->fm(100);
        $this->assertEquals("Field100", $found);

        $found = $record->fm(200, 'a');
        $this->assertEquals("Заглавие", $found);

        $found = $record->fm(200, 'z');
        $this->assertNull($found);

        $found = $record->fm(200);
        $this->assertEquals('', $found);

        $found = $record->fm(300, 'a');
        $this->assertNull($found);
    }

    public function testFma_1()
    {
        $record = new MarcRecord();
        $record->add(100, "Field100");
        $record->add(200)
            ->add('a', "Заглавие")
            ->add('e', "Подзаголовочные сведения");
        $this->assertEquals(2, count($record->fields));

        $found = $record->fma(200);
        $this->assertEquals(0, count($found));
    }

    public function testFma_2()
    {
        $record = new MarcRecord();
        $record->add(100, "Field100/1");
        $record->add(100, "Field100/2");
        $this->assertEquals(2, count($record->fields));

        $found = $record->fma(100);
        $this->assertEquals(2, count($found));

        $found = $record->fma(200);
        $this->assertEquals(0, count($found));
    }

    public function testGetField_1()
    {
        $record = new MarcRecord();
        $record->add(100, "Field100");
        $record->add(200)
            ->add('a', "Заглавие")
            ->add('e', "Подзаголовочные сведения");
        $this->assertEquals(2, count($record->fields));

        $found = $record->getField(100);
        $this->assertEquals("Field100", $found->value);

        $found = $record->getField(200);
        $this->assertEquals("Заглавие", $found->subfields[0]->value);

        $found = $record->getField(300);
        $this->assertNull($found);
    }

    public function testGetField_2()
    {
        $record = new MarcRecord();
        $record->add(100, "Field100/1");
        $record->add(100, "Field100/2");
        $this->assertEquals(2, count($record->fields));

        $found = $record->getField(100);
        $this->assertEquals("Field100/1", $found->value);

        $found = $record->getField(100, 1);
        $this->assertEquals("Field100/2", $found->value);

        $found = $record->getField(100, 2);
        $this->assertNull($found);
    }

    public function testGetFields_1()
    {
        $record = new MarcRecord();
        $record->add(100, "Field100/1");
        $record->add(100, "Field100/2");
        $this->assertEquals(2, count($record->fields));

        $found = $record->getFields(100);
        $this->assertEquals(2, count($found));

        $found = $record->getFields(200);
        $this->assertEquals(0, count($found));
    }

    public function testIsDeleted_1()
    {
        $record = new MarcRecord();
        $this->assertFalse($record->isDeleted());

        $record->status |= LOGICALLY_DELETED;
        $this->assertTrue($record->isDeleted());
    }

    public function testToString_1()
    {
        $record = new MarcRecord();
        $record->mfn = 123;
        $record->version = 234;
        $record->status = 345;
        $record->add(100, "Field100/1");
        $record->add(100, "Field100/2");
        $record->add(200)
            ->add('a', "Заглавие")
            ->add('e', "Подзаголовочные сведения");

        $text = strval($record);
        $this->assertEquals("123#345\x1F\x1E0#234\x1F\x1E100#Field100/1\x1F\x1E100#Field100/2\x1F\x1E200#^aЗаглавие^eПодзаголовочные сведения\x1F\x1E", $text);
    }
}
