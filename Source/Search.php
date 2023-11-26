<?php

/** @noinspection PhpUnused */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Irbis;

/**
 * Search expression builder.
 * @package Irbis
 */
final class Search
{
    private $_buffer = '';

    /**
     * Получение всех документов в базе данных.
     * @return Search Запрос, пригодный для комбинирования.
     */
    public static function all()
    {
        $result = new self();
        $result->_buffer = "I=$";
        return $result;
    } // function all

    /**
     * Логическое И.
     * @return $this Запрос, пригодный для комбинирования.
     */
    public function and_()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer .= ' * ' . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function and_

    /**
     * Поиск записей, удовлетворяющих указанному условию.
     * Допускается несколько условий, все они считаются
     * связанными логическим ИЛИ.
     * @param $prefix string|array Префикс инверсии (или несколько префиксов).
     * @return Search Запрос, пригодный для комбинирования.
     */
    public static function equals($prefix)
    {
        $result = new self();
        if (is_array($prefix)) {
            $values = $prefix;
            $prefix = $values[0];
        } else {
            $values = func_get_args();
        }
        if (count($values) < 2) {
            return $result;
        }
        array_shift($values);
        if (count($values) > 1) {
            $result->_buffer = '(';
        }
        $result->_buffer .= self::wrapIfNeeded($prefix . $values[0]);
        array_shift($values);
        foreach ($values as $value) {
            $result->_buffer .= ' + ' . self::wrapIfNeeded($prefix . $value);
        }
        if (count($values)) {
            $result->_buffer .= ')';
        }

        return $result;
    } // function equals

    /**
     * Нужно ли заключать текст в двойные кавычки?
     * @param $text mixed Проверяемый текст.
     * @return bool Кавычки нужны?.
     */
    public static function needWrap($text)
    {
        $text = (string)$text;
        if (empty($text)) {
            return true;
        }

        $c = $text[0];
        if ($c === '"' || $c === '(') {
            return false;
        }

        if (strpos($text, ' ') !== false
            || strpos($text, '+') !== false
            || strpos($text, '*') !== false
            || strpos($text, '^') !== false
            || strpos($text, '#') !== false
            || strpos($text, '(') !== false
            || strpos($text, ')') !== false
            || strpos($text, '"') !== false) {
            return true;
        }

        return false;
    } // function needWrap

    /**
     * Логическое НЕ.
     * @param $text mixed Входящий запрос.
     * @return $this Запрос, пригодный для комбинирования.
     */
    public function not($text)
    {
        $this->_buffer = '('
            . $this->_buffer
            . ' ^ '
            . self::wrapIfNeeded($text)
            . ')';
        return $this;
    } // function not

    /**
     * Логическое ИЛИ.
     * @return $this Запрос, пригодный для комбинирования.
     */
    public function or_()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer .= ' + ' . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function or_

    /**
     * Поиск в полях с одинаковой меткой.
     * @return $this Запрос, пригодный для комбинирования.
     */
    public function sameField()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer .= ' (G) ' . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function sameField

    /**
     * Поиск в том же повторении поля.
     * @return $this Запрос, пригодный для комбинирования.
     */
    public function sameRepeat()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer .= ' (F) ' . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function sameField

    /**
     * Оборачивание текста в кавычки при необходимости.
     * @param $text mixed Входной текст.
     * @return string Выходной текст.
     */
    public static function wrapIfNeeded($text)
    {
        $value = (string)$text;
        if (self::needWrap($value)) {
            return '"' . $value . '"';
        }
        return $value;
    } // function wrapIfNeeded

    public function __toString()
    {
        return $this->_buffer;
    } // function __toString

} // class Search

/**
 * Ключевые слова (можно несколько!).
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function keyword()
{
    $args = func_get_args();
    array_unshift($args, 'K=');
    return Search::equals($args);
} // function keyword

/**
 * Автор/редактор (как индивидуальный, так и коллективный,
 * как постоянный, так и временный).
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function author()
{
    $args = func_get_args();
    array_unshift($args, 'A=');
    return Search::equals($args);
} // function author

/**
 * Заглавие документа (включая параллельные или альтернативные).
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function title()
{
    $args = func_get_args();
    array_unshift($args, 'T=');
    return Search::equals($args);
} // function title

/**
 * Инвентарный номер (или штрих-код или RFID).
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function number()
{
    $args = func_get_args();
    array_unshift($args, 'IN=');
    return Search::equals($args);
} // function number

/**
 * Издающая организация (издательство).
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function publisher()
{
    $args = func_get_args();
    array_unshift($args, 'O=');
    return Search::equals($args);
} // function publisher

/**
 * Место издания (город).
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function place()
{
    $args = func_get_args();
    array_unshift($args, 'MI=');
    return Search::equals($args);
} // function place

/**
 * Предметная рубрика.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function subject()
{
    $args = func_get_args();
    array_unshift($args, 'S=');
    return Search::equals($args);
} // function subject

/**
 * Язык основного текста (двухсимвольный код).
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function language()
{
    $args = func_get_args();
    array_unshift($args, 'J=');
    return Search::equals($args);
} // function language

/**
 * Год издания.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function year()
{
    $args = func_get_args();
    array_unshift($args, 'G=');
    return Search::equals($args);
} // function year

/**
 * Заглавие журнала.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function magazine()
{
    $args = func_get_args();
    array_unshift($args, 'TJ=');
    return Search::equals($args);
} // function magazine

/**
 * Вид документа.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function documentKind()
{
    $args = func_get_args();
    array_unshift($args, 'V=');
    return Search::equals($args);
} // function documentKind

/**
 * Индекс УДК.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function udc()
{
    $args = func_get_args();
    array_unshift($args, 'U=');
    return Search::equals($args);
} // function udc

/**
 * Индекс ББК.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function bbk()
{
    $args = func_get_args();
    array_unshift($args, 'bbk=');
    return Search::equals($args);
} // function bbk

/**
 * Раздел знаний.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function rzn()
{
    $args = func_get_args();
    array_unshift($args, 'RZN=');
    return Search::equals($args);
} // function rzn

/**
 * Место хранения экземпляра.
 * @return Search Поисковый запрос, пригодный для комбинирования.
 */
function mhr()
{
    $args = func_get_args();
    array_unshift($args, 'MHR=');
    return Search::equals($args);
} // function mhr
