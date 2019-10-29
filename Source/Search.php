<?php

/**
 * Search expression builder.
 */
final class Search
{
    private $_buffer = '';

    /**
     * All documents in the database.
     * @return string
     */
    public static function all()
    {
        $result = new Search();
        $result->_buffer = "I=$";
        return $result;
    } // function all

    /**
     * Logical AND.
     * @return $this
     */
    public function and_()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer = $this->_buffer
                . ' * '
                . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function and_

    /**
     * Search for matching records.
     * @param $prefix
     * @return Search
     */
    public static function equals($prefix)
    {
        $result = new Search();
        if (is_array($prefix)) {
            $values = $prefix;
            $prefix = $values[0];
        } else {
            $values = func_get_args();
        }
        if (count($values) < 2)
            return $result;
        array_shift($values);
        if (count($values) > 1) {
            $result->_buffer = '(';
        }
        $result->_buffer .= self::wrapIfNeeded($prefix . $values[0]);
        array_shift($values);
        foreach ($values as $value)
            $result->_buffer = $result->_buffer
                . ' + '
                . self::wrapIfNeeded($prefix . $value);
        if (count($values))
            $result->_buffer .= ')';
        return $result;
    } // function equals

    /**
     * Need to wrap the text?
     * @param $text
     * @return bool
     */
    public static function needWrap($text)
    {
        $text = (string)$text;
        if (empty($text))
            return true;
        $c = $text[0];
        if ($c == '"' or $c == '(')
            return false;
        if (strpos($text, ' ') !== false
            or strpos($text, '+') !== false
            or strpos($text, '*') !== false
            or strpos($text, '^') !== false
            or strpos($text, '#') !== false
            or strpos($text, '(') !== false
            or strpos($text, ')') !== false
            or strpos($text, '"') !== false)
            return true;
        return false;
    } // function needWrap

    /**
     * Logical NOT.
     * @param $text
     * @return $this
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
     * Logical OR.
     * @return $this
     */
    public function or_()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer = $this->_buffer
                . ' + '
                . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function or_

    /**
     * Search in the same field.
     * @return $this
     */
    public function sameField()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer = $this->_buffer
                . ' (G) '
                . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function sameField

    /**
     * Search in the same field repeat.
     * @return $this
     */
    public function sameRepeat()
    {
        $this->_buffer = '(' . $this->_buffer;
        foreach (func_get_args() as $item) {
            $this->_buffer = $this->_buffer
                . ' (F) '
                . self::wrapIfNeeded($item);
        }
        $this->_buffer .= ')';
        return $this;
    } // function sameField

    /**
     * Wrap the text if needed.
     * @param $text
     * @return string
     */
    public static function wrapIfNeeded($text)
    {
        $value = (string)$text;
        if (self::needWrap($value))
            return '"' . $value . '"';
        return $value;
    } // function wrapIfNeeded

    public function __toString()
    {
        return $this->_buffer;
    } // function __toString

} // class Search

function keyword()
{
    $args = func_get_args();
    array_unshift($args, 'K=');
    return Search::equals($args);
} // function keyword

function author()
{
    $args = func_get_args();
    array_unshift($args, 'A=');
    return Search::equals($args);
} // function author

function title()
{
    $args = func_get_args();
    array_unshift($args, 'T=');
    return Search::equals($args);
} // function title

function number()
{
    $args = func_get_args();
    array_unshift($args, 'IN=');
    return Search::equals($args);
} // function number

function publisher()
{
    $args = func_get_args();
    array_unshift($args, 'O=');
    return Search::equals($args);
} // function publisher

function place()
{
    $args = func_get_args();
    array_unshift($args, 'MI=');
    return Search::equals($args);
} // function place

function subject()
{
    $args = func_get_args();
    array_unshift($args, 'S=');
    return Search::equals($args);
} // function subject

function language()
{
    $args = func_get_args();
    array_unshift($args, 'J=');
    return Search::equals($args);
} // function language

function year()
{
    $args = func_get_args();
    array_unshift($args, 'G=');
    return Search::equals($args);
} // function year

function magazine()
{
    $args = func_get_args();
    array_unshift($args, 'TJ=');
    return Search::equals($args);
} // function magazine

function documentKind()
{
    $args = func_get_args();
    array_unshift($args, 'V=');
    return Search::equals($args);
} // function documentKind

function udc()
{
    $args = func_get_args();
    array_unshift($args, 'U=');
    return Search::equals($args);
} // function udc

function bbk()
{
    $args = func_get_args();
    array_unshift($args, 'bbk=');
    return Search::equals($args);
} // function bbk

function rzn()
{
    $args = func_get_args();
    array_unshift($args, 'RZN=');
    return Search::equals($args);
} // function rzn

function mhr()
{
    $args = func_get_args();
    array_unshift($args, 'MHR=');
    return Search::equals($args);
} // function mhr
