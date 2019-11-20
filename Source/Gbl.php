<?php

namespace Irbis;

require_once __DIR__ . '/PhpIrbis.php';

/**
 * Class Gbl Построитель глобальной корректировки.
 * @package Irbis
 */
final class Gbl
{
    private $_parameters = array();
    private $_statements = array();

    /**
     * Генерация результата по заданным значениям.
     * @return GblSettings
     */
    public function build() {
        $result = new GblSettings();
        $result->parameters = $this->_parameters;
        $result->statements = $this->_statements;
        return $result;
    } // function build

    /**
     * Задание параметра.
     * @param $value
     * @param $title
     * @return $this
     */
    public function parameter($value, $title)
    {
        $param = new GblParameter();
        $param->value = $value;
        $param->title = $title;
        array_push($this->_parameters, $param);
        return $this;
    } // function parameter

    /**
     * Произвольный оператор.
     * @param $command
     * @param string $parameter1
     * @param string $parameter2
     * @param string $format1
     * @param string $format2
     * @return $this
     */
    public function statement
        (
            $command,
            $parameter1 = 'XXX',
            $parameter2 = 'XXX',
            $format1 = 'XXX',
            $format2 = 'XXX'
        )
    {
        $statement = new GblStatement($command, $parameter1,
            $parameter2, $format1, $format2);
        array_push($this->_statements, $statement);

        return $this;
    } // function statement

    /**
     * Обработка вложенных операторов.
     * @param $array
     * @param int $skip
     * @throws \Exception
     */
    private function nestedStatements($array, $skip=0)
    {
        if (count($array) == 0) {
            return;
        }

        for ($i=0; $i < $skip; $i +=1) {
            array_shift($array);
        }

        $gbl = $array[0];
        if ($gbl instanceof Gbl) {
            foreach ($gbl->_statements as $stmt) {
                array_push($this->_statements, $stmt);
            }
        } else if ($gbl instanceof GblStatement) {
            foreach ($array as $stmt) {
                array_push($this->_statements, $stmt);
            }
        } else {
            throw new \Exception("unexpected");
        }
    } // function nestedStatements

    /**
     * Добавление нового повторения поля в заданное (существующее или нет) поле.
     * @param $field
     * @param $value
     * @return Gbl
     */
    public function add($field, $value)
    {
        return $this->statement
            (
                'ADD',
                $field,
                '*',
                $value
            );
    } // function add

    /**
     * Оператор можно использовать в группе операторов после операторов NEWMFN или CORREC.
     * Он дополняет записи всеми полями текущей записи. Т.е. это способ, например, создать
     * новую запись и наполнить ее содержимым текущей записи. Или можно вызвать на корректировку
     * другую запись (CORREC), очистить ее (EMPTY) и наполнить содержимым текущей записи.
     * @return Gbl
     */
    public function all()
    {
        return $this->statement('ALL');
    } // function all

    /**
     * Замена данных в поле или в подполе.
     * @param $field
     * @param $from
     * @param $to
     * @return Gbl
     */
    public function change($field, $from, $to)
    {
        return $this->statement
            (
                'CHA',
                $field,
                '*',
                $from,
                $to
            );
    } // function change

    /**
     * Замена данных в поле или в подполе с учётом регистра символов.
     * @param $field
     * @param $from
     * @param $to
     * @return Gbl
     */
    public function changeWithCase($field, $from, $to)
    {
        return $this->statement
            (
                'CHAC',
                $field,
                '*',
                $from,
                $to
            );
    } // function change

    /**
     * Комментарий. Может находиться между другими операторами и содержать любой текст.
     * @param $text
     * @return Gbl
     */
    public function comment($text)
    {
        return $this->statement('//', $text);
    } // function statement

    /**
     * Из текущей записи вызывает на корректировку другие записи,
     * отобранные по поисковым терминам  из текущей или другой базы данных.
     * @param $database
     * @param $modelField
     * @param $expression
     * @return Gbl
     * @throws \Exception
     */
    public function correct($database, $modelField, $expression)
    {
        $this->statement('CORREC', $database, $modelField, $expression);
        $this->nestedStatements(func_get_args(), 3);
        return $this->statement('END');
    } // function correct

    /**
     * Удаляет поле или подполе.
     * @param $field
     * @param string $repeat
     * @param string $format
     * @return Gbl
     */
    public function delete($field, $repeat = '*', $format='XXX')
    {
        return $this->statement
            (
                'DEL',
                $field,
                $repeat,
                $format
            );
    } // function delete

    /**
     * Удаляет записи, поданные на корректировку. Не требует никаких дополнительных данных.
     * @return Gbl
     */
    public function deleteRecord()
    {
        return $this->statement('DELR');
    } // function deleteRecord

    /**
     * Очищает (опустошает) текущую запись.
     * @return Gbl
     */
    public function empty_()
    {
        return $this->statement('EMPTY');
    }

    /**
     * Определяет условие выполнения операторов, следующих за ним до оператора FI.
     * Состоит из двух строк: первая строка – имя оператора IF; вторая строка - формат,
     * результатом которого может быть строка ‘1’, что означает разрешение
     * на выполнение последующих операторов, или любое другое значение,
     * что означает запрет на выполнение последующих операторов.
     * @param $condition
     * @return Gbl
     * @throws \Exception
     */
    public function if_($condition)
    {
        $this->statement('IF', $condition);
        $this->nestedStatements(func_get_args(), 1);
        return $this->statement('FI');
    } // function if_

    /**
     * Создаёт новую запись в текущей или другой базе данных.
     * @param $database
     * @return Gbl
     * @throws \Exception
     */
    public function newMfn($database)
    {
        $this->statement('NEWMFN', $database);
        $this->nestedStatements(func_get_args(), 1);
        return $this->statement('END');
    } // function newMfn

    /**
     * Формирование пользовательского протокола.
     * @param $text
     * @return Gbl
     */
    public function putlog($text)
    {
        return $this->statement('PUTLOG', $text);
    } // function putlog

    /**
     * Операторы REPEAT-UNTIL организуют цикл выполнения группы операторов.
     * Группа операторов между ними будет выполняться до тех пор, пока формат
     * в операторе UNTIL будет давать значение ‘1’.
     * @param $untilCondition
     * @return Gbl
     * @throws \Exception
     */
    public function repeat($untilCondition)
    {
        $this->statement('REPEAT');
        $this->nestedStatements(func_get_args(), 1);
        return $this->statement('UNTIL', $untilCondition);
    } // function repeat

    public function replace($field, $to)
    {
        return $this->statement('REP', $field, '*', $to);
    } // function replace

    /**
     * Восстанавливает записи в диапазоне MFN, который задан в форме ГЛОБАЛЬНОЙ.
     * Не требует никаких дополнительных данных. Операторы, следующие за данным,
     * выполняются на восстановленных записях.
     * @return Gbl
     */
    public function undelete()
    {
        return $this->statement('UNDEL');
    } // function undelete

    /**
     * Переход к одной из предыдущих копий записи (откат).
     * @param $version
     * @return Gbl
     */
    public function undo($version)
    {
        return $this->statement('UNDOR', $version);
    } // function undo

    public function __toString()
    {
        $result = strval(count($this->_parameters));

        foreach ($this->_parameters as $param) {
            $result = $result . "\n" . $param->value . "\n" . $param->title;
        }

        foreach ($this->_statements as $stmt) {
            $result = $result . "\n" . $stmt->command . "\n" .
                    $stmt->parameter1 . "\n" .
                    $stmt->parameter2 . "\n" .
                    $stmt->format1 . "\n" .
                    $stmt->format2;
        }
        return $result;
    } // function __toString

} // class Gbl
