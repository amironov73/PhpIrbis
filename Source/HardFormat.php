<?php

namespace Irbis;

//
// Простой захардкоженный форматтер для ИРБИС64.
// Требует PHP 5.4 или выше.
// Работает с сервером ИРБИС64 2014 и выше.
//

require_once __DIR__ . '/PhpIrbis.php';

/**
 * Доставание последнего не-пробельного символа из строки.
 * @param string $text Строка, из которой должен быть извлечен последний символ.
 * @return string Последний символ, либо '\0'.
 */
function _get_last_char($text) {
    $position = strlen($text) - 1;
    while ($position >= 0) {
        $result = $text[$position];
        if (!ctype_space($result)) {
            return $result;
        }

        $position--;
    }

    return '\0';
} // function _get_last_char

/**
 * Добавление точки в конец строки.
 * @param string $text Текст, к которому должна быть добавлена точка.
 * @return string Текст с добавленной точкой.
 */
function _add_dot($text) {
    $last = _get_last_char($text);
    if ($last === '.') {
        return $text;
    }
    return $text . '. ';
} // function _add_dot

/**
 * Добавление разделителя областей описания.
 * @param string $text Текст, к которому должен быть добавлен разделитель.
 * @return string Текст с добавленным разделителем.
 */
function _add_separator($text) {
    if (endsWith($text, '. - ')) {
        return $text;
    }
    return $text . '. - ';
}

/**
 * Добавление фрагмента к тексту.
 * @param string $text Текст, к которому должен быть добавлен фрагмент.
 * @param string $chunk Добавляемый фрагмент.
 * @return string Результат конкатенации.
 */
function _append($text, $chunk) {
    if (!empty ($chunk)) {
        return $text . $chunk;
    }

    return $text;
}

/**
 * Добавление фрагмента к тексту с пробелом.
 * @param string $text Текст, к которому должен быть добавлен фрагмент.
 * @param string $chunk Добавляемый фрагмент.
 * @param string $suffix Опциональный суффикс.
 * @return string Результат конкатенации.
 */
function _append_with_space($text, $chunk, $suffix = null) {
    if (!empty($chunk)) {
        $last = '\0';
        if ($text) {
            $last = $text[strlen($text) - 1];
        }

        if (!ctype_space($last)) {
            $text .= ' ';
        }

        return $text . $chunk . $suffix;
    }

    return $text;
}

/**
 * Добавление к тексту фрагмента с префиксом.
 * @param string $text Текст, к которому должен быть добавлен фрагмент.
 * @param string $chunk Добавляемый фрагмент.
 * @param string $prefix Префикс.
 * @return string Результат конкатенации.
 */
function _append_with_prefix($text, $chunk, $prefix) {
    if (!empty ($chunk)) {
        return $text . $prefix . $chunk;
    }

    return $text;
}

/**
 * Добавление к тексту фрагмента с суффиксом.
 * @param string $text Текст, к которому должен быть добавлен фрагмент.
 * @param string $chunk Добавляемый фрагмент.
 * @param string $suffix Суффикс.
 * @return string Результат конкатенации.
 */
function _append_with_suffix($text, $chunk, $suffix) {
    if (!empty ($chunk)) {
        return $text . $chunk . $suffix;
    }

    return $text;
}

/**
 * Добавление к тексту куска с префиксом и суффиксом.
 * @param string $text Текст, к которому должен быть добавлен фрагмент.
 * @param string $chunk Добавляемый фрагмент.
 * @param string $prefix Префикс.
 * @param string $suffix Суффикс.
 * @return string Результат конкатенации.
 */
function _append_with_prefix_and_suffix($text, $chunk, $prefix, $suffix) {
    if (!empty($chunk)) {
        return $text . $prefix . $chunk . $suffix;
    }

    return $text;
}

/**
 * Добавление к тексту кодированной информации вида "a-ил.".
 * @param string $text Текст, к которому должен быть добавлен фрагмент.
 * @param string $chunk Добавляемый фрагмент.
 * @param string $prefix Опциональный префикс.
 * @return string Результат конкатенации.
 */
function _append_with_code($text, $chunk, $prefix = null) {
    return $text . $prefix . $chunk;
}

/**
 * Извлечение значения подполя с одним из указанных кодов.
 * @param RecordField $field Поле записи.
 * @param string $code1 Код подполя.
 * @param string $code2 Код подполя.
 * @return string Значение подполя либо пустая строка.
 */
function _fm2($field, $code1, $code2) {
    foreach ($field->subfields as $subfield) {
        if (same_string($subfield->code, $code1)
            ||same_string($subfield->code, $code2)) {
            return $subfield->value;
        }
    }

    return '';
}

/**
 * Извлечение значения подполя с одним из указанных кодов.
 * @param RecordField $field Поле записи.
 * @param string $code1 Код подполя.
 * @param string $code2 Код подполя.
 * @param string $code3 Код подполя.
 * @return string Значение подполя либо пустая строка.
 */
function _fm3($field, $code1, $code2, $code3) {
    foreach ($field->subfields as $subfield) {
        if (same_string($subfield->code, $code1)
            || same_string($subfield->code, $code2)
            || same_string($subfield->code, $code3)) {
            return $subfield->value;
        }
    }

    return '';
}

/**
 * Извлечение значения подполя с одним из указанных кодов.
 * @param RecordField $field Поле записи.
 * @param string $code1 Код подполя.
 * @param string $code2 Код подполя.
 * @param string $code3 Код подполя.
 * @param string $code4 Код подполя.
 * @return string Значение подполя либо пустая строка.
 */
function _fm4($field, $code1, $code2, $code3, $code4) {
    foreach ($field->subfields as $subfield) {
        if (same_string($subfield->code, $code1)
            || same_string($subfield->code, $code2)
            || same_string($subfield->code, $code3)
            || same_string($subfield->code, $code4)) {
            return $subfield->value;
        }
    }

    return '';
}

/**
 * Простой захардкоженный форматтер для ИРБИС64.
 * @package Irbis
 */
final class HardFormat {
    private $_record;

    /**
     * Конструктор.
     */
    public function __construct($_record = null)
    {
        $this->_record = $_record;
    }

    /**
     * Рабочий лист, ассоциированный с записью.
     * @return string Рабочий лист, например, 'ASP'.
     */
    public function get_worksheet() {
        return $this->_record->fm(920);
    }

    /**
     * Автор книги из общей части.
     * @return string Результат расформатирования.
     */
    public function common_author() {
        $result = '';
        foreach ($this->_record->getFields(961) as $field) {
            // автор - заголовок описания?
            if ($field->getFirstSubFieldValue('z')) {
                // фамилия
                $result = _append($result, $field->getFirstSubFieldValue('a'));

                // римские цифры
                $result = _append_with_prefix($result, $field->getFirstSubFieldValue('d'), ' ');

                // инициалы и их расширение
                $result = _append_with_prefix($result, _fm2($field, 'g', 'b'), ', ');

                // точка, отделяющая автора от заглавия
                $result = _add_dot($result);
            }
        }

        return $result;
    }

    /**
     * Из общей части: основные сведения, поле 461
     * @return string Результат расформатирования..
     */
    public function common_info() {
        $result = '';
        $fields = $this->_record->getFields(461);
        $first = true;
        foreach ($fields as $field) {
            // заглавие
            $title = $field->getFirstSubFieldValue('c');

            if (!$first && $title) {
                $result = _add_dot($result);
            }

            $result = _append($result, $title);
        }

        foreach ($fields as $field) {
            // сведения, относящиеся к заглавию
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('e'), ' : ');

            // сведения об ответственности
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('f'), ' / ');
        }

        // точка, отделяющая общую часть от тома
        $result = _add_dot($result);

        return $result;
    }

    /**
     * Первый автор, поле 700.
     * @return string Результат расформатирования.
     */
    public function first_author() {
        $result = '';
        $author = $this->_record->getField(700);
        if ($author) {
            // фамилия
            $result = _append($result, $author->getFirstSubfieldValue('a'));

            // инициалы или их расширение
            $result = _append_with_prefix($result, _fm2($author, 'g', 'b'), ", ");

            // точка, отделяющая автора от заглавия
            $result = _add_dot($result);
        }

        return $result;
    }

    /**
     * Область заглавия, поле 200.
     * @return string Результат расформатирования.
     */
    public function title_area() {
        $result = '';
        $field = $this->_record->getField(200);
        if ($field) {
            // обозначение или номер тома
            $result = _append_with_suffix($result, $field->getFirstSubFieldValue('v'), " : ");

            // заглавие тома
            $result = _append($result, $field->getFirstSubFieldValue('a'));

            // параллельные заглавия
            foreach ($this->_record->getFields(510) as $parallel) {
                $result = _append_with_prefix($result, $parallel->getFirstSubFieldValue('d'), " = ");
            }

            // несколько томов в одной книге
            foreach ($this->_record->getFields(925) as $volume) {
                $result = _append($result, ', ');

                // обозначение и номер тома
                $result = _append_with_suffix($result, $volume->getFirstSubFieldValue('v'), ' : ');

                // заглавие тома
                $result = _append($result, $volume->getFirstSubFieldValue('a'));

                // заглавие второго произведения
                $result = _append_with_prefix($result, $volume->getFirstSubFieldValue('b'), ' ; ');

                $result = _append_with_prefix($result, $volume->getFirstSubFieldValue('c'), ' ; ');
            }

            // статьи сборника без общего заглавия
            foreach ($this->_record->getFields(922) as $article) {
                $result = _add_dot($result);
                $result = _append($result, $article->getFirstSubFieldValue('c'));
            }

            // сведения, относящиеся к заглавию
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('e'), " : ");

            // вторая и третья единицы деления
            foreach ($this->_record->getFields(923) as $issue) {
                $secondUnit = $issue->getFirstSubFieldValue('h'); // обозначение второй единицы деления
                $secondTitle = $issue->getFirstSubFieldValue('i'); // заглавие второй единицы деления
                if ($secondUnit || $secondTitle) {
                    $result = _add_dot($result);
                    $result = _append_with_space($result, $secondUnit);
                    $result = _append_with_space($result, $secondTitle);
                }

                $thirdUnit = $issue->getFirstSubFieldValue('k'); // обозначение третьей единицы деления
                $thirdTitle = $issue->getFirstSubFieldValue('l'); // заглавие третьей единицы деления
                if ($thirdUnit || $thirdTitle) {
                    $result = _add_dot($result);
                    $result = _append_with_space($result, $thirdUnit);
                    $result = _append_with_space($result, $thirdTitle);
                }
            }

            // первые сведения об ответственности
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('f'), " / ");

            // последующие сведения об ответственности
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('g'), " ; ");
        }

        return $result;
    }

    /**
     * Сведения об издании, поле 205.
     * @return string Результат расформатирования.
     */
    public function edition() {
        $result = '';
        $edition = $this->_record->getField (205);
        if ($edition) {
            $result = _add_separator($result);
            $result = _append($result, $edition->getFirstSubFieldValue('a'));
        }

        return $result;
    }

    /**
     * Выходные данные, поле 210.
     * @return string Результат расформатирования.
     */
    public function imprint() {
        $result = '';
        $imprint = $this->_record->getField (210);
        if ($imprint) {
            $result = _add_separator($result);
            $result = _append($result, $imprint->getFirstSubFieldValue('a'));
            $result = _append_with_prefix($result, $imprint->getFirstSubFieldValue('c'), " : ");
            $result = _append_with_prefix($result, $imprint->getFirstSubFieldValue('d'), ", ");
        }

        return $result;
    }

    /**
     * Физические характеристики, поле 215.
     * @return string Результат расформатирования.
     */
    public function physical_characteristics() {
        $result = '';
        foreach ($this->_record->getFields(215) as $field) {
            $result = _add_separator($result);

            // объем (цифры)
            $result = _append($result, $field->getFirstSubFieldValue('a'));

            // единица измерения
            $unit = $field->getFirstSubFieldValue('1');
            if (!$unit) {
                $unit = 'с.';
            }

            $result = _append_with_prefix($result, $unit, ' ');

            // иллюстрации
            $result = _append_with_code($result, $field->getFirstSubFieldValue('c'), ' : ');

            // сопроводительный материал
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('e'), ' + ');

            // единица измерения сопроводительного материала
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('2'), ' ');

            // размер
            $result = _append_with_prefix_and_suffix($result, $field->getFirstSubFieldValue('d'), ' ; ', ' см.');

            // тираж
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('x'), '. - Тираж ');
        }

        return $result;
    }

    /**
     * Область серии, поле 225.
     * @return string Результат расформатирования.
     */
    public function series() {
        $result = '';
        foreach ($this->_record->getFields(225) as $field) {
            $result = _add_separator($result);
            $result = _append_with_code($result, '(');

            // наименование (заглавие) серии
            $result = _append($result, $field->getFirstSubFieldValue('a'));

            // параллельное наименование серии
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('d'), ' = ');

            // сведения, относящиеся к наименованию серии
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('e'), ' : ');

            // ISSN
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('x'), '. - ISSN ');

            // номер выпуска
            $result = _append_with_prefix($result, $field->getFirstSubFieldValue('v'), " ; ");

            $result = _append_with_code($result, ').');
        }

        return $result;
    }

    /**
     * ISBN и цена, поле 10.
     * @return string Результат расформатирования.
     */
    public function isbn_and_price() {
        $result = '';
        foreach ($this->_record->getFields(10) as $field) {
            $isbn = $field->getFirstSubFieldValue('a');
            $erroneous = $field->getFirstSubFieldValue('z');
            $price = $field->getFirstSubFieldValue('d');
            if (!empty($isbn) || !empty($erroneous) || !empty($price)) {
                $prefix = '';
                if ($isbn) {
                    $result = _append_with_prefix($result, $isbn, '. - ISBN ');
                    $prefix = ' : ';
                }

                if ($erroneous) {
                    $result = _append_with_prefix_and_suffix($result, $erroneous, '. - ISBN ', ' (ошибочный)');
                    $prefix = ' : ';
                }

                $result = _append_with_prefix($result, $price, $prefix);

                // валюта
                $currency = $field->getFirstSubFieldValue('c');
                if (!$currency) {
                    $currency = 'руб.';
                }

                $result = _append_with_prefix($result, $currency, ' ');
            }
        }

        return $result;
    }

    /**
     * Идентификационный номер нетекстового материала, поле 19.
     * @return string Результат расформатирования.
     */
    public function identifier() {
        $result = '';
        foreach ($this->_record->getFields(19) as $field) {
            // основной документ или приложение?
            $mainDocument = $field->getFirstSubFieldValue('x');
            if ($mainDocument === '0') {
                $result = _add_separator($result);

                // тип номера
                $result = _append($result, $field->getFirstSubFieldCode('a'));

                // собственно номер
                $result = _append_with_prefix($result, $field->getFirstSubFieldCode('b'), ' ');
            }
        }

        return $result;
    }

    /**
     * Вид содержания, средства доступа и характеристика содержания.
     * @return string Результат расформатирования.
     */
    public function kind_of_content() {
        $result = '';
        foreach ($this->_record->getFields(203) as $field) {
            $result = _add_separator($result);
            $result = _append($result, $field->getFirstSubfieldValue('a'));
            $result = _append_with_prefix_and_suffix($result, $field->getFirstSubfieldValue('p'), " (", ")");
            $result = _append_with_prefix($result, $field->getFirstSubfieldValue('c'), " : ");
        }
        return $result;
    }

    /**
     * Краткое библиографическое описание.
     * @param MarcRecord $record Новая запись (опционально, если не задать,
     * используется старая запись.
     * @return string Результат расформатирования.
     */
    public function brief($record = null) {
        if ($record) {
            $this->_record = $record;
        }

        $result = $this->common_author()
            . $this->common_info()
            . $this->first_author()
            . $this->title_area()
            . $this->edition()
            . $this->imprint()
            . $this->physical_characteristics()
            . $this->series()
            . $this->isbn_and_price()
            . $this->identifier()
            . $this->kind_of_content();

        $result = _add_dot($result);

        return trim($result);
    }
}
