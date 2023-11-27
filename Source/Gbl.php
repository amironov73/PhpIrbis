<?php

/** @noinspection PhpUnused */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Irbis;

require_once __DIR__ . '/PhpIrbis.php';

/**
 * Class Gbl Построитель глобальной корректировки (ГК).
 * @package Irbis
 */
final class Gbl
{
    // параметры ГК, часто отсутствуют
    private $_parameters = array();

    // операторы ГК, должен быть хотя бы один, иначе ГК не имеет смысла
    private $_statements = array();

    /**
     * Выдача настроек ГК по заданным значениям.
     * @return GblSettings
     */
    public function build() {
        $result = new GblSettings();
        $result->parameters = $this->_parameters;
        $result->statements = $this->_statements;
        return $result;
    } // function build

    /**
     * Задание параметра ГК.
     * @param mixed $value Значение параметра.
     * @param string $title Опциональное наименование параметра.
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function parameter($value, $title)
    {
        $param = new GblParameter();
        $param->value = $value;
        $param->title = $title;
        $this->_parameters[] = $param;
        return $this;
    } // function parameter

    /**
     * Добавление произвольного оператора к текущей ГК.
     * @param string $command Код команды, например, 'ADD' мли 'DEL'.
     * @param mixed $parameter1 Первый параметр, как правило, спецификация поля/подполя.
     * @param mixed $parameter2 Второй параметр, как правило, спецификация повторения.
     * @param string $format1 Первый формат, например, выражение для замены.
     * @param string $format2 Второй формат, например, заменяющее выражение.
     * @return Gbl $this Для цепочечных вызовов.
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
        $this->_statements[] = $statement;

        return $this;
    } // function statement

    /**
     * Обработка вложенных операторов ГК.
     * @param array $array Массив вложенных операторов ГК.
     * @param int $skip Количество пропускаемых элементов (как правило, не задается).
     * @throws \Exception
     */
    private function nestedStatements($array, $skip=0)
    {
        if (count($array) === 0) {
            return;
        }

        for ($i=0; $i < $skip; ++$i) {
            array_shift($array);
        }

        $gbl = $array[0];
        if ($gbl instanceof self) {
            foreach ($gbl->_statements as $stmt) {
                $this->_statements[] = $stmt;
            }
        } else if ($gbl instanceof GblStatement) {
            foreach ($array as $stmt) {
                $this->_statements[] = $stmt;
            }
        } else {
            throw new \RuntimeException("unexpected");
        }
    } // function nestedStatements

    /**
     * Добавление нового повторения поля к заданному (существующему
     * или нет - неважно) полю.
     * @param int|string $field Метка добавляемого поля, например, '700'.
     * @param mixed $value Значение добавляемого поля в формате ИРБИС,
     * например, '^aМиронов^bА.^gАлексей'.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function all()
    {
        return $this->statement('ALL');
    } // function all

    /**
     * Замена данных в поле или в подполе.
     * @param int|string $field Метка поля/подполя, например, '700^b'.
     * @param mixed $from Заменяемое значение, например, 'А.В.'.
     * @param mixed $to Заменяющее значение, например, 'А. В.'.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @param int|string $field Метка поля/подполя, например, '700^b'.
     * @param mixed $from Заменяемое значение, например, 'А.В.'.
     * @param mixed $to Заменяющее значение, например, 'А. В.'.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @param mixed $text Произвольный текст комментария.
     * ИРБИС никак не интерпретирует его.
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function comment($text)
    {
        return $this->statement('//', $text);
    } // function statement

    /**
     * Из текущей записи вызывает на корректировку другие записи,
     * отобранные по поисковым терминам из текущей или другой базы данных.
     * @param string $database Имя базы данных, например, 'IBIS'.
     * Если строка – ‘*’, то этой базой данных останется текущая.
     * @param string $modelField Строка, которая передается в корректируемые
     * записи в виде «модельного» поля с меткой 1001. Т.е. это способ передачи
     * данных от текущей записи в корректируемые. Следует не забывать в последнем
     * операторе группы удалять поле 1001.
     * @param string $expression Строки, которые будут рассматриваться как термины
     * словаря другой (или той же) базы данных. Записи, связанные с этими терминами,
     * будут далее корректироваться. Если последним символом термина будет
     * символ ‘$’ (усечение), то отбор записей на корректировку будет аналогичен
     * проведению в другой базе данных поиска ‘термин$’.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @param int|string $field Метка удаляемого поля/подполя.
     * @param string $repeat Спецификация повторения.
     * @param string $format Если повторение поля задано признаком F,
     * то удаляются повторения в зависимости от значения строк,
     * полученных расформатированием данного аргумента.
     * Если значение строки ‘1’, то соответствующее по порядку
     * повторение удаляется, иначе нет.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function deleteRecord()
    {
        return $this->statement('DELR');
    } // function deleteRecord

    /**
     * Очищает (опустошает) текущую запись.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @param mixed $condition Условие, например 'a(v700)'.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @param string $database Имя базы данных, например, 'IBIS'.
     * @return Gbl $this Для цепочечных вызовов.
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
     * @param mixed $text Произвольный текст, выводимый в протокол.
     * ИРБИС его никак не интерпретирует.
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function putlog($text)
    {
        return $this->statement('PUTLOG', $text);
    } // function putlog

    /**
     * Операторы REPEAT-UNTIL организуют цикл выполнения группы операторов.
     * Группа операторов между ними будет выполняться до тех пор, пока формат
     * в операторе UNTIL будет давать значение ‘1’.
     * @param mixed $untilCondition Условие продолжения цикла.
     * @return Gbl $this Для цепочечных вызовов.
     * @throws \Exception
     */
    public function repeat($untilCondition)
    {
        $this->statement('REPEAT');
        $this->nestedStatements(func_get_args(), 1);
        return $this->statement('UNTIL', $untilCondition);
    } // function repeat

    /**
     * Замена целиком поля или подполя на новое значение.
     * @param int|string $field Спецификация поля/подполя, например, '700^a'.
     * @param mixed $to Заменяющий текст, например, 'Пушкин'.
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function replace($field, $to)
    {
        return $this->statement('REP', $field, '*', $to);
    } // function replace

    /**
     * Восстанавливает записи в диапазоне MFN, который задан в форме ГЛОБАЛЬНОЙ.
     * Не требует никаких дополнительных данных. Операторы, следующие за данным,
     * выполняются на восстановленных записях.
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function undelete()
    {
        return $this->statement('UNDEL');
    } // function undelete

    /**
     * Переход к одной из предыдущих копий записи (откат).
     * @param mixed $version На сколько шагов необходимо вернуться.
     * '*' = исходная версия записи. Пусто = нет действий.
     * @return Gbl $this Для цепочечных вызовов.
     */
    public function undo($version)
    {
        if (!$version) {
            return $this->statement('UNDOR');
        }
        return $this->statement('UNDOR', $version);
    } // function undo

    public function __toString()
    {
        $result = (string) count($this->_parameters);

        foreach ($this->_parameters as $param) {
            $result .= "\n" . $param->value . "\n" . $param->title;
        }

        foreach ($this->_statements as $stmt) {
            $result .=  "\n" . $stmt->command . "\n" .
                    $stmt->parameter1 . "\n" .
                    $stmt->parameter2 . "\n" .
                    $stmt->format1 . "\n" .
                    $stmt->format2;
        }
        return $result;
    } // function __toString

} // class Gbl
