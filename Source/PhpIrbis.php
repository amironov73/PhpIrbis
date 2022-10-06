<?php

/** @noinspection PhpUnused */
/** @noinspection PhpUnnecessaryLocalVariableInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Irbis;

//
// Простой клиент для АБИС ИРБИС64.
// Требует PHP 5.4 или выше.
// Работает с сервером ИРБИС64 2014 и выше.
//

// Кодировки
//
// ВАЖНО! Предполагается, что внутренняя кодировка символов в PHP -- UTF-8
// И строковые литералы в PHP-файлах хранятся также в кодировке UTF-8
// Если это не так, возможны проблемы
//

// Статус записи

const LOGICALLY_DELETED  = 1;  ///< Запись логически удалена
const PHYSICALLY_DELETED = 2;  ///< Запись физически удалена
const ABSENT             = 4;  ///< Запись отсутствует
const NON_ACTUALIZED     = 8;  ///< Запись не актуализирована
const LAST_VERSION       = 32; ///< Последняя версия записи
const LOCKED_RECORD      = 64; ///< Запись заблокирована на ввод

// Распространённые форматы

const ALL_FORMAT       = "&uf('+0')";  ///< Полные данные по полям
const BRIEF_FORMAT     = '@brief';     ///< Краткое библиографическое описание
const IBIS_FORMAT      = '@ibiskw_h';  ///< Формат IBIS (старый)
const INFO_FORMAT      = '@info_w';    ///< Информационный формат
const OPTIMIZED_FORMAT = '@';          ///< Оптимизированный формат

// Распространённые поиски

const KEYWORD_PREFIX    = 'K=';  ///< Ключевые слова
const AUTHOR_PREFIX     = 'A=';  ///< Индивидуальный автор, редактор, составитель
const COLLECTIVE_PREFIX = 'M=';  ///< Коллектив или мероприятие
const TITLE_PREFIX      = 'T=';  ///< Заглавие
const INVENTORY_PREFIX  = 'IN='; ///< Инвентарный номер, штрих-код или радиометка
const INDEX_PREFIX      = 'I=';  ///< Шифр документа в базе

// Логические операторы для поиска

const LOGIC_OR                = 0; ///< Только ИЛИ
const LOGIC_OR_AND            = 1; ///< ИЛИ и И
const LOGIC_OR_AND_NOT        = 2; ///< ИЛИ, И, НЕТ (по умолчанию)
const LOGIC_OR_AND_NOT_FIELD  = 3; ///< ИЛИ, И, НЕТ, И (в поле)
const LOGIC_OR_AND_NOT_PHRASE = 4; ///< ИЛИ, И, НЕТ, И (в поле), И (фраза)

// Коды АРМ

const ADMINISTRATOR = 'A'; ///< Адмнистратор
const CATALOGER     = 'C'; ///< Каталогизатор
const ACQUSITIONS   = 'M'; ///< Комплектатор
const READER        = 'R'; ///< Читатель
const CIRCULATION   = 'B'; ///< Книговыдача
const BOOKLAND      = 'B'; ///< Книговыдача
const PROVISION     = 'K'; ///< Книгообеспеченность

// Команды глобальной корректировки

const ADD_FIELD        = 'ADD';    ///< добавление нового повторения поля или подполя в заданное существующее поле
const DELETE_FIELD     = 'DEL';    ///< удаляет поле или подполе в поле
const REPLACE_FIELD    = 'REP';    ///< замена целиком поля или подполя
const CHANGE_FIELD     = 'CHA';    ///< замена данных в поле или в подполе
const CHANGE_WITH_CASE = 'CHAC';   ///< замена данных в поле или в подполе с учетом регистра символов
const DELETE_RECORD    = 'DELR';   ///< удаляет записи, поданные на корректировку
const UNDELETE_RECORD  = 'UNDELR'; ///< восстанавливает записи
const CORRECT_RECORD   = 'CORREC'; ///< вызывает на корректировку другие записи, отобранные по поисковым терминам  из текущей или другой, доступной в системе, базы данных
const CREATE_RECORD    = 'NEWMFN'; ///< создание новой записи в текущей или другой базе данных
const EMPTY_RECORD     = 'EMPTY';  ///< очищает (опустошает) текущую запись
const UNDO_RECORD      = 'UNDOR';  ///< переход к одной из предыдущих копий записи (откат)
const GBL_END          = 'END';    ///< закрывающая операторная скобка
const GBL_IF           = 'IF';     ///< логическое ветвление
const GBL_FI           = 'FI';     ///< закрывающий оператор для ветвления
const GBL_ALL          = 'ALL';    ///< дополняет записи всеми полями текущей записи
const GBL_REPEAT       = 'REPEAT'; ///< цикл из группы операторов
const GBL_UNTIL        = 'UNTIL';  ///< закрывающий оператор для цикла
const PUTLOG           = 'PUTLOG'; ///< формирование пользовательского протокола

/**
 * @brief Разделитель строк в ИРБИС.
 */
const IRBIS_DELIMITER = "\x1F\x1E";

/**
 * @brief Короткие версии разделителя строк ИРБИС.
 */
const SHORT_DELIMITER = "\x1E";
const ALT_DELIMITER   = "\x1F";

/**
 * @brief Пустая ли данная строка?
 *
 * @param string $text Строка для изучения.
 * @return bool
 */
function is_null_or_empty($text)
{
    return (!isset($text) || !$text || !trim($text));
} // function is_null_or_empty

/**
 * @brief Строки совпадают с точностью до регистра символов?
 *
 * @param string $str1 Первая строка.
 * @param string $str2 Вторая строка.
 * @return bool
 */
function same_string($str1, $str2)
{
    return strcasecmp($str1, $str2) === 0;
} // function same_string

/**
 * @brief Безопасное получение элемента массива по индексу.
 *
 * @param array $a Массив.
 * @param int $ofs Индекс.
 * @return mixed|null
 */
function safe_get(array $a, $ofs)
{
    if (isset($a[$ofs])) {
        return $a[$ofs];
    }
    return null;
} // function safe_get

// Таблица ручной перекодировки из CP1251 в UTF8.
$_ansiTable = array
(
    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18,
    19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34,
    35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50,
    51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66,
    67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,
    83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98,
    99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111,
    112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124,
    125, 126, 127, 1026, 1027, 8218, 1107, 8222, 8230, 8224, 8225,
    8364, 8240, 1033, 8249, 1034, 1036, 1035, 1039, 1106, 8216, 8217,
    8220, 8221, 8226, 8211, 8212, 152, 8482, 1113, 8250, 1114, 1116,
    1115, 1119, 160, 1038, 1118, 1032, 164, 1168, 166, 167, 1025, 169,
    1028, 171, 172, 173, 174, 1031, 176, 177, 1030, 1110, 1169, 181,
    182, 183, 1105, 8470, 1108, 187, 1112, 1029, 1109, 1111, 1040,
    1041, 1042, 1043, 1044, 1045, 1046, 1047, 1048, 1049, 1050, 1051,
    1052, 1053, 1054, 1055, 1056, 1057, 1058, 1059, 1060, 1061, 1062,
    1063, 1064, 1065, 1066, 1067, 1068, 1069, 1070, 1071, 1072, 1073,
    1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084,
    1085, 1086, 1087, 1088, 1089, 1090, 1091, 1092, 1093, 1094, 1095,
    1096, 1097, 1098, 1099, 1100, 1101, 1102, 1103
);

/**
 * @brief Ручная перекодировка текста из CP1251 в UTF-8.
 *
 */
function ansiToUtf ($text)
{
    global $_ansiTable;

    $length = strlen ($text);
    $result = '';

    for ($i = 0; $i < $length; $i++) {
        $chr = ord ($text[$i]);
        $chr = $_ansiTable[$chr];
        if ($chr < 128) {
            $result .= chr ($chr);
        }
        else {
            $result .= chr (($chr >> 6) | 0xC0);
            $result .= chr (($chr & 0x3F) | 0x80);
        }
    }

    return $result;
} // function ansiToUtf

/**
 * @brief Ручная перекодировка текста из CP1251 в UTF-8.
 *
 */
function utfToAnsi ($text)
{
    global $_ansiTable;

    $length = strlen ($text);
    $result = '';
    $offset = 0;

    while ($offset < $length) {
        $chr = ord ($text[$offset++]);
        if ($chr < 128) {
            $result .= chr ($chr);
        }
        else {
            if (($chr & 0xE0) === 0xC0) {
                $wide =  ($chr & 0x1F) << 6;
                $wide |= (ord ($text[$offset++]) & 0x3F);
                $chr = '?';
                for ($i = 128; $i < 256; $i++) {
                    // начинать с 0 нет смысла, сэкономим такты процессора
                    if ($_ansiTable[$i] === $wide) {
                        $chr = chr ($i);
                        break;
                    }
                }

                $result .= $chr;
            }
            else if (($chr & 0xF0) === 0xE0) {
                $result .= '?';
                $offset++;
                $offset++;
            }
            else {
                $result .= '?';
                $offset++;
                $offset++;
                $offset++;
            }
        }

    }

    return $result;
} // function utfToAnsi

/**
 * @brief Замена переводов строки с ИРБИСных на обычные.
 *
 * @param string $text Текст для замены.
 * @return mixed Текст с замененными переводами строки.
 */
function irbis_to_dos($text)
{
    return str_replace(IRBIS_DELIMITER, "\n", $text);
} // function irbis_to_dos

/**
 * @brief Разбивка текста на строки по ИРБИСным разделителям.
 *
 * @param string $text Текст для разбиения.
 * @return array Массив строк.
 */
function irbis_to_lines($text)
{
    return explode(IRBIS_DELIMITER, $text);
} // function irbis_to_lines

/**
 * @brief Удаление комментариев из строки.
 *
 * @param string $text Текст для удаления комментариев.
 * @return string Очищенный текст.
 */
function remove_comments($text)
{
    if (is_null_or_empty($text)) {
        return $text;
    }

    if (strpos($text, '/*') == false) {
        return $text;
    }

    $result = '';
    $state = '';
    $index = 0;
    $length = strlen($text);

    while ($index < $length) {
        $c = $text[$index];

        switch ($state) {
            case "'":
            case '"':
            case '|':
                if ($c == $state) {
                    $state = '';
                }

                $result .= $c;
                break;

            default:
                if ($c === '/') {
                    if ($index + 1 < $length && $text[$index + 1] === '*') {
                        while ($index < $length) {
                            $c = $text[$index];
                            if ($c === "\r" || $c === "\n") {
                                $result .= $c;
                                break;
                            }

                            $index++;
                        }
                    } else {
                        $result .= $c;
                    }
                } else if ($c === "'" || $c === '"' || $c === '|') {
                    $state = $c;
                    $result .= $c;
                } else {
                    $result .= $c;
                }
                break;
        }

        $index++;
    }

    return $result;
} // function remove_comments

/**
 * @brief Подготовка динамического формата
 * для передачи на сервер.
 *
 * В формате должны отсутствовать комментарии
 * и служебные символы (например, перевод
 * строки или табуляция).
 *
 * @param string $text Текст для обработки.
 * @return string Обработанный текст.
 */
function prepare_format($text)
{
    $text = remove_comments($text);
    $length = strlen($text);
    if (!$length) {
        return $text;
    }

    $flag = false;
    for ($i = 0; $i < $length; $i++) {
        if ($text[$i] < ' ') {
            $flag = true;
            break;
        }
    }

    if (!$flag) {
        return $text;
    }

    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $c = $text[$i];
        if ($c >= ' ') {
            $result .= $c;
        }
    }

    return $result;
} // function prepare_format

/**
 * @brief Получение описания по коду ошибки, возвращенному сервером.
 *
 * @param int $code Код ошибки.
 * @return string Словесное описание ошибки.
 */
function describe_error($code)
{
    if ($code >= 0) {
        return 'Нет ошибки';
    }

    $errors = array(
        -100 => 'Заданный MFN вне пределов БД',
        -101 => 'Ошибочный размер полки',
        -102 => 'Ошибочный номер полки',
        -140 => 'MFN вне пределов БД',
        -141 => 'Ошибка чтения',
        -200 => 'Указанное поле отсутствует',
        -201 => 'Предыдущая версия записи отсутствует',
        -202 => 'Заданный термин не найден (термин не существует)',
        -203 => 'Последний термин в списке',
        -204 => 'Первый термин в списке',
        -300 => 'База данных монопольно заблокирована',
        -301 => 'База данных монопольно заблокирована',
        -400 => 'Ошибка при открытии файлов MST или XRF (ошибка файла данных)',
        -401 => 'Ошибка при открытии файлов IFP (ошибка файла индекса)',
        -402 => 'Ошибка при записи',
        -403 => 'Ошибка при актуализации',
        -600 => 'Запись логически удалена',
        -601 => 'Запись физически удалена',
        -602 => 'Запись заблокирована на ввод',
        -603 => 'Запись логически удалена',
        -605 => 'Запись физически удалена',
        -607 => 'Ошибка autoin.gbl',
        -608 => 'Ошибка версии записи',
        -700 => 'Ошибка создания резервной копии',
        -701 => 'Ошибка восстановления из резервной копии',
        -702 => 'Ошибка сортировки',
        -703 => 'Ошибочный термин',
        -704 => 'Ошибка создания словаря',
        -705 => 'Ошибка загрузки словаря',
        -800 => 'Ошибка в параметрах глобальной корректировки',
        -801 => 'ERR_GBL_REP',
        -802 => 'ERR_GBL_MET',
        -1111 => 'Ошибка исполнения сервера (SERVER_EXECUTE_ERROR)',
        -2222 => 'Ошибка в протоколе (WRONG_PROTOCOL)',
        -3333 => 'Незарегистрированный клиент (ошибка входа на сервер) (клиент не в списке)',
        -3334 => 'Клиент не выполнил вход на сервер (клиент не используется)',
        -3335 => 'Неправильный уникальный идентификатор клиента',
        -3336 => 'Нет доступа к командам АРМ',
        -3337 => 'Клиент уже зарегистрирован',
        -3338 => 'Недопустимый клиент',
        -4444 => 'Неверный пароль',
        -5555 => 'Файл не существует',
        -6666 => 'Сервер перегружен. Достигнуто максимальное число потоков обработки',
        -7777 => 'Не удалось запустить/прервать поток администратора (ошибка процесса)',
        -8888 => 'Общая ошибка',
        -100001 => 'Ошибка создания сокета',
        -100002 => 'Сбой сети',
        -100003 => 'Не подключен к серверу'
    );

    return $errors[$code] ?: 'Неизвестная ошибка';
} // function describe_error

/**
 * @brief "Хорошие" коды для readRecord.
 *
 * @return array "Хорошие" коды для readRecord.
 */
function codes_for_read_record()
{
    return array(-201, -600, -602, -603);
} // function codes_for_read_record

/**
 * @brief "Хорошие" коды для readTerms.
 *
 * @return array "Хорошие" коды для readTerms.
 */
function codes_for_read_terms()
{
    return array(-202, -203, -204);
} // function codes_for_read_terms

/**
 * @brief Специфичное для ИРБИС исключение.
 */
final class IrbisException extends \Exception
{
    /**
     * @brief Конструктор.
     *
     * @param string $message Сообщение об ошибке.
     * @param int $code Код ошибки.
     * @param mixed $previous Вложенное исключение.
     */
    public function __construct($message = "",
                                $code = 0,
                                $previous = null)
    {
        parent::__construct($message, $code, $previous);
    } // function __construct

    /**
     * @return string Текстовое представление исключения.
     */
    public function __toString()
    {
        return __CLASS__ . ": [$this->code]: $this->message\n";
    } // function __toString
} // class IrbisException

/**
 * @brief Подполе записи. Состоит из кода и значения.
 */
final class SubField
{
    /**
     * @var string Код подполя.
     */
    public $code;

    /**
     * @var string Значение подполя.
     */
    public $value;

    /**
     * @brief Конструктор подполя.
     *
     * @param string $code Код подполя.
     * @param string $value Значение подполя.
     */
    public function __construct($code = '', $value = '')
    {
        $this->code = $code;
        $this->value = $value;
    } // function __construct

    /**
     * @brief Клонирование подполя.
     */
    public function __clone()
    {
        $this->value = str_repeat($this->value, 1);
    } // function __clone

    /**
     * @brief Декодирование подполя из протокольного представления.
     *
     * @param string $line
     */
    public function decode($line)
    {
        $this->code = $line[0];
        $this->value = substr($line, 1);
    } // function decode

    /**
     * @brief Верификация подполя.
     *
     * @param bool $throw Бросать ли исключение при ошибке?
     * @return bool Результат верификации.
     * @throws IrbisException
     */
    public function verify($throw = true)
    {
        $result = $this->code && $this->value;
        if (!$result && $throw) {
            throw new IrbisException();
        }

        return $result;
    } // function verify

    public function __toString()
    {
        return '^' . $this->code . $this->value;
    } // function __toString
} // class SubField

/**
 * @brief Поле записи. Состоит из метки и (опционального) значения.
 *
 * Может содержать произвольное количество подполей.
 */
final class RecordField
{
    /**
     * @var int Метка поля.
     */
    public $tag;

    /**
     * @var string Значение поля до первого разделителя.
     */
    public $value;

    /**
     * @var array Массив подполей.
     */
    public $subfields = array();

    /**
     * @brief Конструктор поля.
     *
     * @param int $tag Метка поля.
     * @param string $value Значение поля.
     */
    public function __construct($tag = 0, $value = '')
    {
        $this->tag = $tag;
        $this->value = $value;
    } // function __construct

    /**
     * @brief Клонирование поля.
     */
    public function __clone()
    {
        $this->value = str_repeat($this->value, 1);
        $new = array();
        foreach ($this->subfields as $i => $subfield) {
            $new[$i] = clone $subfield;
        }
        $this->subfields = $new;
    } // function __clone

    /**
     * @brief Добавление подполя с указанными кодом и значением.
     *
     * @param string $code Код подполя.
     * @param string $value Значение подполя.
     * @return $this
     */
    public function add($code, $value)
    {
        $subfield = new SubField();
        $subfield->code = $code;
        $subfield->value = $value;
        $this->subfields[] = $subfield;

        return $this;
    } // function add

    /**
     * @brief Очищает поле (удаляет значение и все подполя).
     *
     * @return $this
     */
    public function clear()
    {
        $this->value = '';
        $this->subfields = array();

        return $this;
    } // function clear

    /**
     * @brief Декодирование поля из протокольного представления.
     *
     * @param string $line
     */
    public function decode($line)
    {
        $this->tag = intval(strtok($line, "#"));
        $body = strtok('');

        if ($body[0] === '^') {
            $this->value = '';
            $all = explode('^', $body);
        } else {
            $this->value = strtok($body, '^');
            $all = explode('^', strtok(''));
        }

        foreach ($all as $one) {
            if (!empty($one)) {
                $sf = new SubField();
                $sf->decode($one);
                $this->subfields[] = $sf;
            }
        }
    } // function decode

    /**
     * @brief Получает массив встроенных полей из данного поля.
     *
     * @return array Встроенные поля.
     */
    public function getEmbeddedFields()
    {
        $result = array();
        $found = null;
        foreach ($this->subfields as $subfield) {
            if ($subfield->code == '1') {
                if ($found) {
                    if (count($found->subfields) || $found->value) {
                        $result[] = $found;
                    }
                    $found = null;
                }
                $value = $subfield->value;
                if (!$value)
                    continue;

                $tag = intval(substr($value, 0, 3));
                $found = new RecordField($tag);
                if ($tag < 10) {
                    $found->value = substr($value, 3);
                }
            } else {
                if ($found) {
                    $found->subfields[] = $subfield;
                }
            }
        }

        if ($found) {
            if (count($found->subfields) || $found->value) {
                $result[] = $found;
            }
        }

        return $result;
    } // function getEmbeddedFields

    /**
     * @brief Возвращает первое вхождение подполя с указанным кодом.
     *
     * @param string $code Код искомого подполя.
     * @return SubField|null Найденное подполе.
     */
    public function getFirstSubfield($code)
    {
        foreach ($this->subfields as $subfield) {
            if (same_string($subfield->code, $code)) {
                return $subfield;
            }
        }

        return null;
    } // function getFirstSubfield

    /**
     * @brief Возвращает значение первого вхождения подполя с указанным кодом.
     *
     * @param string $code Код искомого подполя.
     * @return string Значение найденного подполя либо пустая строка.
     */
    public function getFirstSubfieldValue($code)
    {
        foreach ($this->subfields as $subfield) {
            if (same_string($subfield->code, $code)) {
                return $subfield->value;
            }
        }

        return '';
    } // function getFirstSubfieldValue

    /**
     * @brief Вставляет подполе по указанному индексу.
     *
     * @param int $index Позиция для вставки.
     * @param SubField $subfield Подполе.
     */
    public function insertAt($index, SubField $subfield)
    {
        array_splice($this->subfields, $index, 0, $subfield);
    } // function insertAt

    /**
     * @brief Удаляет подполе по указанному индексу.
     *
     * @param int $index Индекс для удаления.
     */
    public function removeAt($index)
    {
        unset($this->subfields[$index]);
        $this->subfields = array_values($this->subfields);
    } // function removeAt

    /**
     * @brief Удаляет все подполя с указанным кодом.
     *
     * @param string $code Искомый код подполя.
     */
    public function removeSubfield($code)
    {
        $flag = false;
        $len = count($this->subfields);
        for ($i = 0; $i < $len; ++$i) {
            $sub = $this->subfields[$i];
            if (same_string($sub->code, $code)) {
                unset($this->subfields[$i]);
                $flag = true;
            }
        }
        if ($flag) {
            $this->subfields = array_values($this->subfields);
        }
    } // function removeSubfield

    /**
     * @brief Устанавливает значение подполя с указанным кодом.
     *
     * @param string $code Искомый код подполя.
     * @param string $value Новое значение подполя.
     * @return $this
     */
    public function setSubfield($code, $value)
    {
        if (!$value) {
            $this->removeSubfield($code);
        }
        else {
            $subfield = $this->getFirstSubfield($code);
            if (!$subfield) {
                $this->add($code, $value);
                $subfield = $this->getFirstSubfield($code);
            }

            $subfield->value = $value;
        }

        return $this;
    } // function setSubfield

    /**
     * @brief Верификация поля.
     *
     * @param bool $throw Бросать ли исключение при ошибке?
     * @return bool Результат верификации.
     * @throws IrbisException Ошибка в структуре поля.
     */
    public function verify($throw = true)
    {
        $result = $this->tag && ($this->value || count($this->subfields));
        if ($result && $this->subfields) {
            foreach ($this->subfields as $subfield) {
                $result = $subfield->verify($throw);
                if (!$result) {
                    break;
                }
            }
        }

        if (!$result && $throw) {
            throw new IrbisException();
        }

        return $result;
    } // function verify

    public function __toString()
    {
        $result = $this->tag . '#' . $this->value;

        foreach ($this->subfields as $sf) {
            $result .= $sf;
        }

        return $result;
    } // function __toString
} // class RecordField

/**
 * @brief Запись. Состоит из произвольного количества полей.
 */
final class MarcRecord
{
    /**
     * @var string Имя базы данных, в которой хранится запись.
     */
    public $database = '';

    /**
     * @var int MFN записи.
     */
    public $mfn = 0;

    /**
     * @var int Версия записи.
     */
    public $version = 0;

    /**
     * @var int Статус записи.
     */
    public $status = 0;

    /**
     * @var array Массив полей.
     */
    public $fields = array();

    public function __clone()
    {
        $this->database = str_repeat($this->database, 1);
        $new = array();
        foreach ($this->fields as $i => $field) {
            $new[$i] = clone $field;
        }
        $this->fields = $new;
    } // function __clone

    /**
     * Добавление поля в запись.
     *
     * @param int $tag Метка поля.
     * @param string $value Значение поля до первого разделителя.
     * @return RecordField Созданное поле.
     */
    public function add($tag, $value = '')
    {
        $field = new RecordField();
        $field->tag = $tag;
        $field->value = $value;
        $this->fields[] = $field;

        return $field;
    } // function add

    /**
     * Очистка записи (удаление всех полей).
     *
     * @return $this
     */
    public function clear()
    {
        $this->fields = array();

        return $this;
    } // function clear

    /**
     * Декодирование ответа сервера.
     *
     * @param array $lines Массив строк
     * с клиентским представлением записи.
     */
    public function decode(array $lines)
    {
        if (empty($lines) || count($lines) < 2) {
            return;
        }

        // mfn and status of the record
        $firstLine = explode('#', $lines[0]);
        $this->mfn = intval($firstLine[0]);
        $this->status = intval(safe_get($firstLine, 1));

        // version of the record
        $secondLine = explode('#', $lines[1]);
        $this->version = intval(safe_get($secondLine, 1));
        $lines = array_slice($lines, 2);

        // fields
        foreach ($lines as $line) {
            if ($line) {
                $field = new RecordField();
                $field->decode($line);
                $this->fields[] = $field;
            }
        }
    } // function decode

    /**
     * Кодирование записи в протокольное представление.
     *
     * @param string $delimiter Разделитель строк.
     * В зависимости от ситуации ИРБИСный или обычный.
     * @return string
     */
    public function encode($delimiter = IRBIS_DELIMITER)
    {
        $result = $this->mfn . '#' . $this->status . $delimiter
            . '0#' . $this->version . $delimiter;

        foreach ($this->fields as $field) {
            $result .= ($field . $delimiter);
        }

        return $result;
    } // function encode

    /**
     * Получение значения поля (или подполя)
     * с указанной меткой (и указанным кодом).
     *
     * @param int $tag Метка поля
     * @param string $code Код подполя
     * @return string|null
     */
    public function fm($tag, $code = '')
    {
        foreach ($this->fields as $field) {
            if ($field->tag === $tag) {
                if ($code) {
                    foreach ($field->subfields as $subfield) {
                        if (strcasecmp($subfield->code, $code) === 0) {
                            return $subfield->value;
                        }
                    }
                } else {
                    return $field->value;
                }
            }
        }

        return null;
    } // function fm

    /**
     * Получение массива значений поля (или подполя)
     * с указанной меткой (и указанным кодом).
     *
     * @param int $tag Искомая метка поля.
     * @param string $code Код подполя.
     * @return array
     */
    public function fma($tag, $code = '')
    {
        $result = array();
        foreach ($this->fields as $field) {
            if ($field->tag === $tag) {
                if ($code) {
                    foreach ($field->subfields as $subfield) {
                        if (strcasecmp($subfield->code, $code) === 0) {
                            if ($subfield->value) {
                                $result[] = $subfield->value;
                            }
                        }
                    }
                } else {
                    if ($field->value) {
                        $result[] = $field->value;
                    }
                }
            }
        }

        return $result;
    } // function fma

    /**
     * Получение указанного поля (с учётом повторения).
     *
     * @param int $tag Метка поля.
     * @param int $occurrence Номер повторения.
     * @return RecordField|null
     */
    public function getField($tag, $occurrence = 0)
    {
        foreach ($this->fields as $field) {
            if ($field->tag === $tag) {
                if (!$occurrence) {
                    return $field;
                }

                $occurrence--;
            }
        }

        return null;
    } // function getField

    /**
     * Получение массива полей с указанной меткой.
     *
     * @param int $tag Искомая метка поля.
     * @return array
     */
    public function getFields($tag)
    {
        $result = array();
        foreach ($this->fields as $field) {
            if ($field->tag === $tag) {
                $result[] = $field;
            }
        }

        return $result;
    } // function getFields

    /**
     * Определяет, удалена ли запись?
     *
     * @return bool Запись удалена
     * (неважно - логически или физически)?
     */
    public function isDeleted()
    {
        return ($this->status & 3) !== 0;
    } // function is_deleted

    /**
     * Вставляет поле по указанному индексу.
     *
     * @param int $index Позиция для вставки.
     * @param RecordField $field Поле.
     */
    public function insertAt($index, RecordField $field)
    {
        array_splice($this->fields, $index, 0, $field);
    } // function insertAt

    /**
     * Удаляет поле по указанному индексу.
     *
     * @param int $index Индекс для удаления.
     */
    public function removeAt($index)
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    } // function removeAt

    /**
     * Удаляет все поля с указанной меткой.
     *
     * @param int $tag Индекс для удаления.
     */
    public function removeField($tag)
    {
        $flag = false;
        $len = count($this->fields);
        for ($i = 0; $i < $len; $i = $i + 1) {
            $field = $this->fields[$i];
            if ($field->tag == $tag) {
                unset($this->fields[$i]);
                $flag = true;
            }
        }
        if ($flag) {
            $this->fields = array_values($this->fields);
        }
    } // function removeField

    /**
     * Сброс состояния записи, отвязка её от базы данных.
     * Поля данных остаются при этом нетронутыми.
     *
     * @return $this
     */
    public function reset()
    {
        $this->mfn = 0;
        $this->status = 0;
        $this->version = 0;
        $this->database = '';

        return $this;
    } // function reset

    /**
     * @brief Установка значения поля до первого разделителя.
     *
     * @param int $tag Искомая метка поля.
     * @param string $value Новое значение до первого разделителя.
     * @return $this
     */
    public function setValue($tag, $value)
    {
        if (!$value) {
            $this->removeField($tag);
        }
        else {
            $field = $this->getField($tag);
            if (!$field) {
                $field = $this->add($tag);
            }

            $field->value = $value;
        }

        return $this;
    } // function setValue

    /**
     * @brief Установка значения подполя.
     *
     * @param int $tag Метка поля.
     * @param string $code Искомый код подполя.
     * @param string $value Новое значение подполя.
     * @return $this
     */
    public function setSubfield($tag, $code, $value)
    {
        $field = $this->getField($tag);
        if (!$value) {
            if ($field) {
                $field->removeSubfield($code);
            }
        }
        else {
            if (!$field) {
                $field = $this->add($tag);
            }

            $field->setSubfield($code, $value);
        }

        return $this;
    } // function setSubfield

    /**
     * Верификация записи.
     *
     * @param bool $throw Бросать ли исключение при ошибке?
     * @return bool Результат верификации.
     */
    public function verify($throw = true)
    {
        $result = false;
        foreach ($this->fields as $field) {
            $result = $field->verify($throw);
            if (!$result) {
                break;
            }
        }

        return $result;
    } // function verify

    public function __toString()
    {
        return $this->encode();
    } // function __toStirng
} // class MarcRecord

/**
 * @brief Запись в "сыром" ("неразобранном") виде.
 */
final class RawRecord
{
    /**
     * @var string Имя базы данных.
     */
    public $database = '';

    /**
     * @var string MFN.
     */
    public $mfn = '';

    /**
     * @var string Статус.
     */
    public $status = '';

    /**
     * @var string Версия.
     */
    public $version = '';

    /**
     * @var array Поля записи.
     */
    public $fields = array();

    /**
     * Декодирование ответа сервера.
     *
     * @param array $lines Массив строк
     * с клиентским представлением записи.
     */
    public function decode(array $lines)
    {
        if (empty($lines) || count($lines) < 2) {
            return;
        }

        // mfn and status of the record
        $firstLine = explode('#', $lines[0]);
        $this->mfn = intval($firstLine[0]);
        $this->status = intval(safe_get($firstLine, 1));

        // version of the record
        $secondLine = explode('#', $lines[1]);
        $this->version = intval(safe_get($secondLine, 1));
        $this->fields = array_slice($lines, 2);
    } // function decode

    /**
     * Кодирование записи в протокольное представление.
     *
     * @param string $delimiter Разделитель строк.
     * В зависимости от ситуации ИРБИСный или обычный.
     * @return string
     */
    public function encode($delimiter = IRBIS_DELIMITER)
    {
        $result = $this->mfn . '#' . $this->status . $delimiter
            . '0#' . $this->version . $delimiter;

        foreach ($this->fields as $field) {
            $result .= ($field . $delimiter);
        }

        return $result;
    } // function encode
} // class RawRecord

/**
 * @brief Строка найденной записи в ответе сервера.
 */
final class FoundLine
{
    /**
     * @var bool Материализована?
     */
    public $materialized = false;

    /**
     * @var int Порядковый номер.
     */
    public $serialNumber = 0;

    /**
     * @var int MFN.
     */
    public $mfn = 0;

    /**
     * @var null Иконка.
     */
    public $icon = null;

    /**
     * @var bool Выбрана (помечена).
     */
    public $selected = false;

    /**
     * @var string Библиографическое описание.
     */
    public $description = '';

    /**
     * @var string Ключ для сортировки.
     */
    public $sort = '';

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа сервера.
     * @return array Массив найденных записей с MFN
     * и биб. описанием (опционально).
     */
    public static function parse(array $lines)
    {
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            $item = new FoundLine();
            $item->mfn = intval($parts[0]);
            $item->description = safe_get($parts, 1);
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа сервера.
     * @return array Массив MFN найденных записей.
     */
    public static function parseMfn(array $lines)
    {
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            $mfn = intval($parts[0]);
            $result[] = $mfn;
        }

        return $result;
    }

    /**
     * Преобразование в массив библиографических описаний.
     *
     * @param array $found Найденные записи.
     * @return array Массив описаний.
     */
    public static function toDescription(array $found)
    {
        $result = array();
        foreach ($found as $item) {
            $result[] = $item->description;
        }

        return $result;
    }

    /**
     * Преобразование в массив MFN.
     *
     * @param array $found Найденные записи.
     * @return array Массив MFN.
     */
    public static function toMfn(array $found)
    {
        $result = array();
        foreach ($found as $item) {
            $result[] = $item->mfn;
        }

        return $result;
    }

    public function __toString()
    {
        return $this->description
            ? $this->mfn . '#' . $this->description
            : strval($this->mfn);
    }
} // class FoundLine

/**
 * @brief Пара строк в меню.
 */
final class MenuEntry
{
    /**
     * @var string Код -- первая строка в меню.
     */
    public $code;

    /**
     * @var string Соответствующее коду значение -- вторая строка в меню.
     */
    public $comment;

    public function __toString()
    {
        return $this->code . ' - ' . $this->comment;
    }
} // class MenuEntry

/**
 * @brief Файл меню. Состоит из пар строк (см. MenuEntry).
 */
final class MenuFile
{
    /**
     * @var array Массив пар строк.
     */
    public $entries = array();

    /**
     * Добавление элемента.
     *
     * @param string $code Код элемента.
     * @param string $comment Комментарий.
     * @return $this
     */
    public function add($code, $comment)
    {
        $entry = new MenuEntry();
        $entry->code = $code;
        $entry->comment = $comment;
        $this->entries[] = $entry;

        return $this;
    }

    /**
     * Отыскивает запись, соответствующую данному коду.
     *
     * @param string $code
     * @return mixed|null
     */
    public function getEntry($code)
    {
        foreach ($this->entries as $entry) {
            if (strcasecmp($entry->code, $code) == 0) {
                return $entry;
            }
        }

        $code = trim($code);
        foreach ($this->entries as $entry) {
            if (strcasecmp($entry->code, $code) == 0) {
                return $entry;
            }
        }

        $code = self::trimCode($code);
        foreach ($this->entries as $entry) {
            if (strcasecmp($entry->code, $code) == 0) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Выдает значение, соответствующее коду.
     *
     * @param $code
     * @param string $defaultValue
     * @return string
     */
    public function getValue($code, $defaultValue = '')
    {
        $entry = $this->getEntry($code);
        if (!$entry) {
            return $defaultValue;
        }

        return $entry->comment;
    }

    /**
     * Разбор серверного представления MNU-файла.
     *
     * @param array $lines Массив строк.
     */
    public function parse(array $lines)
    {
        $length = count($lines);
        for ($i = 0; $i < $length; $i += 2) {
            $code = $lines[$i];
            if (!$code || substr($code, 5) === '*****') {
                break;
            }

            $comment = $lines[$i + 1];
            $entry = new MenuEntry();
            $entry->code = $code;
            $entry->comment = $comment;
            $this->entries[] = $entry;
        }
    }

    /**
     * Отрезание лишних символов в коде.
     *
     * @param string $code Код.
     * @return string Очищенный код.
     */
    public static function trimCode($code)
    {
        return trim($code, '-=:');
    }

    public function __toString()
    {
        $result = '';

        foreach ($this->entries as $entry) {
            $result .= ($entry . PHP_EOL);
        }
        $result .= "*****\n";

        return $result;
    }
} // class MenuFile

/**
 * @brief Строка INI-файла. Состоит из ключа
 * и (опционального) значения.
 */
final class IniLine
{
    /**
     * @var string Ключ.
     */
    public $key;

    /**
     * @var string Значение.
     */
    public $value;

    public function __toString()
    {
        return $this->key . ' = ' . $this->value;
    }
} // class IniLine

/**
 * @brief Секция INI-файла. Состоит из строк
 * (см. IniLine).
 */
final class IniSection
{
    /**
     * @var string Имя секции.
     */
    public $name = '';

    /**
     * @var array Строки 'ключ=значение'.
     */
    public $lines = array();

    /**
     * Поиск строки с указанным ключом.
     *
     * @param string $key Имя ключа.
     * @return IniLine|null
     */
    public function find($key)
    {
        foreach ($this->lines as $line) {
            if (same_string($line->key, $key)) {
                return $line;
            }
        }

        return null;
    }

    /**
     * Получение значения для указанного ключа.
     *
     * @param string $key Имя ключа.
     * @param string $defaultValue Значение по умолчанию.
     * @return string Найденное значение или значение
     * по умолчанию.
     */
    public function getValue($key, $defaultValue = '')
    {
        $found = $this->find($key);
        return $found ? $found->value : $defaultValue;
    }

    /**
     * Удаление элемента с указанным ключом.
     *
     * @param string $key Имя ключа.
     * @return IniSection
     */
    public function remove($key)
    {
        $length = count($this->lines);
        for ($i = 0; $i < $length; $i++) {
            if (same_string($this->lines[$i]->key, $key)) {
                unset($this->lines[$i]);
                break;
            }
        }

        return $this;
    }

    /**
     * Установка значения.
     *
     * @param string $key
     * @param string $value
     */
    public function setValue($key, $value)
    {
        if (!$value) {
            $this->remove($key);
        } else {
            $item = $this->find($key);
            if ($item) {
                $item->value = $value;
            } else {
                $item = new IniLine();
                $item->key = $key;
                $item->value = $value;
                $this->lines[] = $item;
            }
        }
    }

    public function __toString()
    {
        $result = '[' . $this->name . ']' . PHP_EOL;

        foreach ($this->lines as $line) {
            $result .= ($line . PHP_EOL);
        }

        return $result;
    }
} // class IniSection

/**
 * @brief INI-файл. Состоит из секций (см. IniSection).
 */
final class IniFile
{
    /**
     * @var array Секции INI-файла.
     */
    public $sections = array();

    /**
     * Поиск секции с указанным именем.
     *
     * @param string $name Имя секции.
     * @return mixed|null
     */
    public function findSection($name)
    {
        foreach ($this->sections as $section) {
            if (same_string($section->name, $name)) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Поиск секции с указанным именем или создание
     * в случае её отсутствия.
     *
     * @param string $name Имя секции.
     * @return IniSection
     */
    public function getOrCreateSection($name)
    {
        $result = $this->findSection($name);
        if (!$result) {
            $result = new IniSection();
            $result->name = $name;
            $this->sections[] = $result;
        }

        return $result;
    }

    /**
     * Получение значения (из одной из секций).
     *
     * @param string $sectionName Имя секции.
     * @param string $key Ключ искомого элемента.
     * @param string $defaultValue Значение по умолчанию.
     * @return string Значение найденного элемента
     * или значение по умолчанию.
     */
    public function getValue($sectionName, $key, $defaultValue = '')
    {
        $section = $this->findSection($sectionName);
        if ($section) {
            return $section->getValue($key, $defaultValue);
        }

        return $defaultValue;
    }

    /**
     * Разбор текстового представления INI-файла.
     *
     * @param array $lines Строки INI-файла.
     */
    public function parse(array $lines)
    {
        $section = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (is_null_or_empty($trimmed)) {
                continue;
            }

            if ($trimmed[0] === '[') {
                $name = substr($trimmed, 1, -1);
                $section = $this->getOrCreateSection($name);
            } else if ($section) {
                $parts = explode('=', $trimmed, 2);
                $key = $parts[0];
                $value = safe_get($parts, 1);
                $item = new IniLine();
                $item->key = $key;
                $item->value = $value;
                $section->lines[] = $item;
            }
        }
    }

    /**
     * Установка значения элемента (в одной из секций).
     *
     * @param string $sectionName Имя секции.
     * @param string $key Ключ элемента.
     * @param string $value Значение элемента.
     * @return $this
     */
    public function setValue($sectionName, $key, $value)
    {
        $section = $this->getOrCreateSection($sectionName);
        $section->setValue($key, $value);

        return $this;
    }

    public function __toString()
    {
        $result = '';
        $first = true;

        foreach ($this->sections as $section) {
            if (!$first) {
                $result .= PHP_EOL;
            }

            $result .= $section;

            $first = false;
        }

        return $result;
    }
} // class IniFile

/**
 * @brief Узел дерева TRE-файла.
 */
final class TreeNode
{
    /**
     * @var array Дочерние узлы.
     */
    public $children = array();

    /**
     * @var string Значение, хранящееся в узле.
     */
    public $value = '';

    /**
     * @var int Уровень вложенности узла.
     */
    public $level = 0;

    /**
     * TreeNode constructor.
     * @param string $value
     */
    public function __construct($value = '')
    {
        $this->value = $value;
    }

    /**
     * Добавление дочернего узла с указанным значением.
     *
     * @param $value
     * @return $this
     */
    public function add($value)
    {
        $child = new TreeNode($value);
        $this->children[] = $child;

        return $this;
    }

    public function __toString()
    {
        return $this->value;
    }
} // class TreeNode

/**
 * @brief Дерево, хранящееся в TRE-файле.
 */
final class TreeFile
{
    /**
     * @var array Корни дерева.
     */
    public $roots = array();

    private static function arrange1(array $list, $level)
    {
        $count = count($list);
        $index = 0;

        while ($index < $count) {
            $next = self::arrange2($list, $level, $index, $count);
            $index = $next;
        }
    }

    private static function arrange2(array $list, $level, $index, $count)
    {
        $next = $index + 1;
        $level2 = $level + 1;

        $parent = $list[$index];
        while ($next < $count) {
            $child = $list[$next];
            if ($child->level < $level) {
                break;
            }

            if ($child->level == $level2) {
                $parent->children[] = $child;
            }

            $next++;
        }

        return $next;
    }

    private static function countIndent($text)
    {
        $result = 0;
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            if ($text[$i] === "\t") {
                $result++;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Добавление корневого элемента.
     *
     * @param string $value Значение элемента.
     * @return TreeNode Созданный элемент.
     */
    public function addRoot($value)
    {
        $result = new TreeNode($value);
        $this->roots[] = $result;

        return $result;
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки с ответом сервера.
     * @throws IrbisException
     */
    public function parse(array $lines)
    {
        if (!count($lines)) {
            return;
        }

        $list = array();
        $currentLevel = 0;
        $line = $lines[0];
        if (self::countIndent($line) != 0) {
            throw new IrbisException();
        }

        $list[] = new TreeNode($line);
        $lines = array_slice($lines, 1);
        foreach ($lines as $line) {
            if (is_null_or_empty($line)) {
                continue;
            }

            $level = self::countIndent($line);
            if ($level > ($currentLevel + 1)) {
                throw new IrbisException();
            }

            $currentLevel = $level;
            $line = substr($line, $currentLevel);
            $node = new TreeNode($line);
            $node->level = $currentLevel;
            $list[] = $node;
        }

        $maxLevel = 0;
        foreach ($list as $item) {
            if ($item->level > $maxLevel) {
                $maxLevel = $item->level;
            }
        }

        for ($level = 0; $level < $maxLevel; $level++) {
            self::arrange1($list, $level);
        }

        foreach ($list as $item) {
            if ($item->level == 0) {
                $this->roots[] = $item;
            }
        }
    }
} // class TreeFile

/**
 * @brief Информация о базе данных ИРБИС.
 */
final class DatabaseInfo
{
    /**
     * @var string Имя базы данных.
     */
    public $name = '';

    /**
     * @var string Описание базы данных.
     */
    public $description = '';

    /**
     * @var int Максимальный MFN.
     */
    public $maxMfn = 0;

    /**
     * @var array Логически удалённые записи.
     */
    public $logicallyDeletedRecords = array();

    /**
     * @var array Физически удалённые записи.
     */
    public $physicallyDeletedRecords = array();

    /**
     * @var array Неактуализированные записи.
     */
    public $nonActualizedRecords = array();

    /**
     * @var array Заблокированные записи.
     */
    public $lockedRecords = array();

    /**
     * @var bool Признак блокировки базы данных в целом.
     */
    public $databaseLocked = false;

    /**
     * @var bool База только для чтения.
     */
    public $readOnly = false;

    static function parseLine($line)
    {
        $result = array();
        $items = explode(SHORT_DELIMITER, $line);
        foreach ($items as $item) {
            $result[] = intval($item);
        }

        return $result;
    }

    /**
     * Разбор ответа сервера (см. getDatabaseInfo).
     *
     * @param array $lines Ответ сервера.
     * @return DatabaseInfo
     */
    public static function parseResponse(array $lines)
    {
        $result = new DatabaseInfo();
        if (!empty($lines)) {
            $result->logicallyDeletedRecords = self::parseLine($lines[0]);
            $result->physicallyDeletedRecords = self::parseLine(safe_get($lines, 1));
            $result->nonActualizedRecords = self::parseLine(safe_get($lines, 2));
            $result->lockedRecords = self::parseLine(safe_get($lines, 3));
            $result->maxMfn = intval(safe_get($lines, 4));
            $result->databaseLocked = intval(safe_get($lines, 5)) != 0;
        }

        return $result;
    }

    /**
     * Получение списка баз данных из MNU-файла.
     *
     * @param MenuFile $menu Меню.
     * @return array
     */
    public static function parseMenu(MenuFile $menu)
    {
        $result = array();
        foreach ($menu->entries as $entry) {
            $name = $entry->code;
            if ($name === '*****') {
                break;
            }

            $description = $entry->comment;
            $readOnly = false;
            if ($name[0] == '-') {
                $name = substr($name, 1);
                $readOnly = true;
            }

            $db = new DatabaseInfo();
            $db->name = $name;
            $db->description = $description;
            $db->readOnly = $readOnly;
            $result[] = $db;
        }

        return $result;
    }

    public function __toString()
    {
        return $this->name;
    }
} // class DatabaseInfo

/**
 * @brief Информация о запущенном на ИРБИС-сервере процессе.
 */
final class ProcessInfo
{
    /**
     * @var string Просто порядковый номер в списке.
     */
    public $number = '';

    /**
     * @var string С каким клиентом взаимодействует.
     */
    public $ipAddress = '';

    /**
     * @var string Логин оператора.
     */
    public $name = '';

    /**
     * @var string Идентификатор клиента.
     */
    public $clientId = '';

    /**
     * @var string Тип АРМ.
     */
    public $workstation = '';

    /**
     * @var string Время запуска.
     */
    public $started = '';

    /**
     * @var string Последняя выполненная
     * (или выполняемая) команда.
     */
    public $lastCommand = '';

    /**
     * @var string Порядковый номер последней команды.
     */
    public $commandNumber = '';

    /**
     * @var string Индентификатор процесса.
     */
    public $processId = '';

    /**
     * @var string Состояние.
     */
    public $state = '';

    public static function parse(array $lines)
    {
        $result = array();
        if (empty($lines) || count($lines) < 2)
            return $result;

        $processCount = intval($lines[0]);
        $linesPerProcess = intval($lines[1]);
        if (!$processCount || !$linesPerProcess) {
            return $result;
        }

        $lines = array_slice($lines, 2);
        for ($i = 0; $i < $processCount; $i++) {
            if (count($lines) < 10)
                break;

            $process = new ProcessInfo();
            $process->number = $lines[0];
            $process->ipAddress = $lines[1];
            $process->name = $lines[2];
            $process->clientId = $lines[3];
            $process->workstation = $lines[4];
            $process->started = $lines[5];
            $process->lastCommand = $lines[6];
            $process->commandNumber = $lines[7];
            $process->processId = $lines[8];
            $process->state = $lines[9];

            $result[] = $process;
            $lines = array_slice($lines, $linesPerProcess);
        }

        return $result;
    }

    public function __toString()
    {
        return "{$this->number} {$this->ipAddress} {$this->name}";
    }
} // class ProcessInfo

/**
 * @brief Информация о версии ИРБИС-сервера.
 */
final class VersionInfo
{
    /**
     * @var string На какое юридическое лицо приобретен сервер.
     */
    public $organization = '';

    /**
     * @var string Собственно версия сервера. Например, 64.2008.1
     */
    public $version = '';

    /**
     * @var int Максимальное количество подключений.
     */
    public $maxClients = 0;

    /**
     * @var int Текущее количество подключений.
     */
    public $connectedClients = 0;

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки с ответом сервера.
     */
    public function parse(array $lines)
    {
        if (count($lines) == 3) {
            $this->version = $lines[0];
            $this->connectedClients = intval($lines[1]);
            $this->maxClients = intval($lines[2]);
        } else {
            $this->organization = $lines[0];
            $this->version = safe_get($lines, 1);
            $this->connectedClients = intval(safe_get($lines, 2));
            $this->maxClients = intval(safe_get($lines, 3));
        }
    }

    public function __toString()
    {
        return $this->version;
    }
} // class VersionInfo

/**
 * Информация о клиенте, подключенном к серверу ИРБИС
 * (не обязательно о текущем).
 */
final class ClientInfo
{
    /**
     * @var string Порядковый номер.
     */
    public $number = '';

    /**
     * @var string Адрес клиента.
     */
    public $ipAddress = '';

    /**
     * @var string Порт клиента.
     */
    public $port = '';

    /**
     * @var string Логин.
     */
    public $name = '';

    /**
     * @var string Идентификатор клиентской программы
     * (просто уникальное число).
     */
    public $id = '';

    /**
     * @var string Клиентский АРМ.
     */
    public $workstation = '';

    /**
     * @var string Момент подключения к серверу.
     */
    public $registered = '';

    /**
     * @var string Последнее подтверждение,
     * посланное серверу.
     */
    public $acknowledged = '';

    /**
     * @var string Последняя команда, посланная серверу.
     */
    public $lastCommand = '';

    /**
     * @var string Номер последней команды.
     */
    public $commandNumber = '';

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа.
     */
    public function parse(array $lines)
    {
        if (empty($lines) || count($lines) < 10)
            return;

        $this->number = $lines[0];
        $this->ipAddress = $lines[1];
        $this->port = $lines[2];
        $this->name = $lines[3];
        $this->id = $lines[4];
        $this->workstation = $lines[5];
        $this->registered = $lines[6];
        $this->acknowledged = $lines[7];
        $this->lastCommand = $lines[8];
        $this->commandNumber = $lines[9];
    }

    public function __toString()
    {
        return $this->ipAddress;
    }
} // class ClientInfo

/**
 * Информация о зарегистрированном пользователе системы
 * (по данным client_m.mnu).
 */
final class UserInfo
{
    /**
     * @var string Номер по порядку в списке.
     */
    public $number = '';

    /**
     * @var string Логин.
     */
    public $name = '';

    /**
     * @var string Пароль.
     */
    public $password = '';

    /**
     * @var string Доступность АРМ Каталогизатор.
     */
    public $cataloger = '';

    /**
     * @var string АРМ Читатель.
     */
    public $reader = '';

    /**
     * @var string АРМ Книговыдача.
     */
    public $circulation = '';

    /**
     * @var string АРМ Комплектатор.
     */
    public $acquisitions = '';

    /**
     * @var string АРМ Книгообеспеченность.
     */
    public $provision = '';

    /**
     * @var string АРМ Администратор.
     */
    public $administrator = '';

    public static function formatPair($prefix, $value, $default)
    {
        if (same_string($value, $default)) {
            return '';
        }

        return $prefix . '=' . $value . ';';
    }

    /**
     * Формирование строкового представления пользователя.
     *
     * @return string
     */
    public function encode()
    {
        return $this->name . "\r\n"
            . $this->password . "\r\n"
            . self::formatPair('C', $this->cataloger, 'irbisc.ini')
            . self::formatPair('R', $this->reader, 'irbisr.ini')
            . self::formatPair('B', $this->circulation, 'irbisb.ini')
            . self::formatPair('M', $this->acquisitions, 'irbism.ini')
            . self::formatPair('K', $this->provision, 'irbisk.ini')
            . self::formatPair('A', $this->administrator, 'irbisa.ini');
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа сервера.
     * @return array
     */
    public static function parse(array $lines)
    {
        $result = array();
        if (empty($lines) || count($lines) < 2)
            return $result;

        $userCount = intval($lines[0]);
        $linesPerUser = intval($lines[1]);
        if (!$userCount || !$linesPerUser)
            return $result;

        $lines = array_slice($lines, 2);
        for ($i = 0; $i < $userCount; $i++) {
            if (empty($lines) || count($lines) < 9)
                break;

            $user = new UserInfo();
            $user->number = $lines[0];
            $user->name = $lines[1];
            $user->password = $lines[2];
            $user->cataloger = $lines[3];
            $user->reader = $lines[4];
            $user->circulation = $lines[5];
            $user->acquisitions = $lines[6];
            $user->provision = $lines[7];
            $user->administrator = $lines[8];
            $result[] = $user;

            $lines = array_slice($lines, $linesPerUser + 1);
        }

        return $result;
    }

    public function __toString()
    {
        return $this->name;
    }
} // class UserInfo

/**
 * Данные для метода printTable.
 */
final class TableDefinition
{
    /**
     * @var string Имя базы данных.
     */
    public $database = '';

    /**
     * @var string Имя таблицы.
     */
    public $table = '';

    /**
     * @var array Заголовки таблицы.
     */
    public $headers = array();

    /**
     * @var string Режим таблицы.
     */
    public $mode = '';

    /**
     * @var string Поисковый запрос.
     */
    public $searchQuery = '';

    /**
     * @var int Минимальный MFN.
     */
    public $minMfn = 0;

    /**
     * @var int Максимальный MFN.
     */
    public $maxMfn = 0;

    /**
     * @var string Запрос для последовательного поиска.
     */
    public $sequentialQuery = '';

    /**
     * @var array Список MFN, по которым строится таблица.
     */
    public $mfnList = array();

    public function __toString()
    {
        return $this->table;
    }
} // class TableDefinition

/**
 * Статистика работы ИРБИС-сервера.
 */
final class ServerStat
{
    /**
     * @var array Подключенные клиенты.
     */
    public $runningClients = array();

    /**
     * @var int Число клиентов, подключенных в текущий момент.
     */
    public $clientCount = 0;

    /**
     * @var int Общее количество команд,
     * исполненных сервером с момента запуска.
     */
    public $totalCommandCount = 0;

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа сервера.
     */
    public function parse(array $lines)
    {
        if (empty($lines) || count($lines) < 2)
            return;

        $this->totalCommandCount = intval($lines[0]);
        $this->clientCount = intval($lines[1]);
        $linesPerClient = intval($lines[2]);
        if (!$linesPerClient) {
            return;
        }

        $lines = array_slice($lines, 3);

        for ($i = 0; $i < $this->clientCount; $i++) {
            $client = new ClientInfo();
            $client->parse($lines);
            if (!$client->name)
                break;
            $this->runningClients[] = $client;
            $lines = array_slice($lines, $linesPerClient + 1);
        }
    }

    public function __toString()
    {
        $result = strval($this->totalCommandCount) . "\n"
            . strval($this->clientCount) . "\n" . '8' . "\n";
        foreach ($this->runningClients as $client) {
            $result .= (strval($client) . "\n");
        }

        return $result;
    }
} // class ServerStat

/**
 * Параметры для запроса постингов с сервера.
 */
final class PostingParameters
{
    /**
     * @var string База данных.
     */
    public $database = '';

    /**
     * @var int Номер первого постинга.
     */
    public $firstPosting = 1;

    /**
     * @var string Формат.
     */
    public $format = '';

    /**
     * @var int Требуемое количество постингов.
     */
    public $numberOfPostings = 0;

    /**
     * @var string Термин.
     */
    public $term = '';

    /**
     * @var array Список термов.
     */
    public $listOfTerms = array();
} // class PostingParameters

/**
 * Параметры для запроса терминов с сервера.
 */
final class TermParameters
{
    /**
     * @var string Имя базы данных.
     */
    public $database = '';

    /**
     * @var int Количество считываемых терминов.
     */
    public $numberOfTerms = 0;

    /**
     * @var bool Возвращать в обратном порядке?
     */
    public $reverseOrder = false;

    /**
     * @var string Начальный термин.
     */
    public $startTerm = '';

    /**
     * @var string Формат.
     */
    public $format = '';
} // class TermParameters

/**
 * Информация о термине поискового словаря.
 */
final class TermInfo
{
    /**
     * @var int Количество ссылок.
     */
    public $count = 0;

    /**
     * @var string Поисковый термин.
     */
    public $text = '';

    public static function parse(array $lines)
    {
        $result = array();
        foreach ($lines as $line) {
            if (!is_null_or_empty($line)) {
                $parts = explode('#', $line, 2);
                $term = new TermInfo();
                $term->count = intval($parts[0]);
                $term->text = safe_get($parts, 1);
                $result[] = $term;
            }
        }

        return $result;
    }

    public function __toString()
    {
        return $this->count . '#' . $this->text;
    }
} // class TermInfo

/**
 * Постинг термина в поисковом индексе.
 */
final class TermPosting
{
    /**
     * @var int MFN записи с искомым термином.
     */
    public $mfn = 0;

    /**
     * @var int Метка поля с искомым термином.
     */
    public $tag = 0;

    /**
     * @var int Повторение поля.
     */
    public $occurrence = 0;

    /**
     * @var int Количество повторений.
     */
    public $count = 0;

    /**
     * @var string Результат форматирования.
     */
    public $text = '';

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа.
     * @return array Массив постингов.
     */
    public static function parse(array $lines)
    {
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 5);
            if (count($parts) < 4) {
                break;
            }

            $item = new TermPosting();
            $item->mfn = intval($parts[0]);
            $item->tag = intval(safe_get($parts, 1));
            $item->occurrence = intval(safe_get($parts, 2));
            $item->count = intval(safe_get($parts, 3));
            $item->text = safe_get($parts, 4);
            $result[] = $item;
        }

        return $result;
    }

    public function __toString()
    {
        return $this->mfn . '#' . $this->tag . '#'
            . $this->occurrence . '#' . $this->count
            . '#' . $this->text;
    }
} // class TermPosting

/**
 * Параметры для поиска записей (метод searchEx).
 */
final class SearchParameters
{
    /**
     * @var string Имя базы данных.
     */
    public $database = '';

    /**
     * @var int Индекс первой требуемой записи.
     */
    public $firstRecord = 1;

    /**
     * @var string Формат для расформатирования записей.
     */
    public $format = '';

    /**
     * @var int Максимальный MFN.
     */
    public $maxMfn = 0;

    /**
     * @var int Минимальный MFN.
     */
    public $minMfn = 0;

    /**
     * @var int Общее число требуемых записей.
     */
    public $numberOfRecords = 0;

    /**
     * @var string Выражение для поиска по словарю.
     */
    public $expression = '';

    /**
     * @var string Выражение для последовательного поиска.
     */
    public $sequential = '';

    /**
     * @var string Выражение для локальной фильтрации.
     */
    public $filter = '';

    /**
     * @var bool Признак кодировки UTF-8.
     */
    public $isUtf = false;

    /**
     * @var bool Признак вложенного вызова.
     */
    public $nested = false;
} // class SearchParameters

/**
 * Сценарий поиска.
 */
final class SearchScenario
{
    /**
     * @var string Название поискового атрибута
     * (автор, инвентарный номер и т. д.).
     */
    public $name = '';

    /**
     * @var string Префикс соответствующих терминов
     * в словаре (может быть пустым).
     */
    public $prefix = '';

    /**
     * @var int Тип словаря для соответствующего поиска.
     */
    public $dictionaryType = 0;

    /**
     * @var string Имя файла справочника.
     */
    public $menuName = '';

    /**
     * @var string Имя формата (без расширения).
     */
    public $oldFormat = '';

    /**
     * @var string Способ корректировки по словарю.
     */
    public $correction = '';

    /**
     * @var string Исходное положение переключателя "Усечение".
     */
    public $truncation = '';

    /**
     * @var string Текст подсказки/предупреждения.
     */
    public $hint = '';

    /**
     * @var string Параметр пока не задействован.
     */
    public $modByDicAuto = '';

    /**
     * @var string Применимые логические операторы.
     */
    public $logic = '';

    /**
     * @var string Правила автоматического расширения поиска
     * на основе авторитетного файла или тезауруса.
     */
    public $advance = '';

    /**
     * @var string Имя формата показа документов.
     */
    public $format = '';

    static function get(IniSection $section, $name, $index)
    {
        $fullName = 'Item' . $name . $index;
        return $section->getValue($fullName);
    }

    /**
     * Разбор INI-файла.
     *
     * @param IniFile $iniFile
     * @return array
     */
    public static function parse(IniFile $iniFile)
    {
        $result = array();
        $section = $iniFile->findSection('SEARCH');
        if ($section) {
            $count = intval($section->getValue('ItemNumb'));
            for ($i = 0; $i < $count; $i++) {
                $scenario = new SearchScenario();
                $scenario->name = self::get($section, "Name", $i);
                $scenario->prefix = self::get($section, "Pref", $i);
                $scenario->dictionaryType = intval(self::get($section, "DictionType", $i));
                $scenario->menuName = self::get($section, "Menu", $i);
                $scenario->oldFormat = '';
                $scenario->correction = self::get($section, "ModByDic", $i);
                $scenario->truncation = self::get($section, "Tranc", $i);
                $scenario->hint = self::get($section, "Hint", $i);
                $scenario->modByDicAuto = self::get($section, "ModByDicAuto", $i);
                $scenario->logic = self::get($section, "Logic", $i);
                $scenario->advance = self::get($section, "Adv", $i);
                $scenario->format = self::get($section, "Pft", $i);
                $result[] = $scenario;
            }
        }

        return $result;
    }
} // class SearchScenario

/**
 * PAR-файл -- содержит пути к файлам базы данных ИРБИС.
 */
final class ParFile
{

    // Пример файла IBIS.PAR:
    //
    // 1=.\datai\ibis\
    // 2=.\datai\ibis\
    // 3=.\datai\ibis\
    // 4=.\datai\ibis\
    // 5=.\datai\ibis\
    // 6=.\datai\ibis\
    // 7=.\datai\ibis\
    // 8=.\datai\ibis\
    // 9=.\datai\ibis\
    // 10=.\datai\ibis\
    // 11=f:\webshare\

    /**
     * @var string Путь к файлу XRF.
     */
    public $xrf = '';

    /**
     * @var string Путь к файлу MST.
     */
    public $mst = '';

    /**
     * @var string Путь к файлу CNT.
     */
    public $cnt = '';

    /**
     * @var string Путь к файлу N01.
     */
    public $n01 = '';

    /**
     * @var string В ИРБИС64 не используется.
     */
    public $n02 = '';

    /**
     * @var string Путь к файлу L01.
     */
    public $l01 = '';

    /**
     * @var string В ИРБИС64 не используется.
     */
    public $l02 = '';

    /**
     * @var string Путь к файлу IFP.
     */
    public $ifp = '';

    /**
     * @var string Путь к файлу ANY.
     */
    public $any = '';

    /**
     * @var string Путь к PFT-файлам.
     */
    public $pft = '';

    /**
     * @var string Расположение внешних объектов (поле 951).
     * Параметр появился в версии 2012.
     */
    public $ext = '';

    /**
     * ParFile constructor.
     * @param string $mst Путь к MST-файлу.
     */
    public function __construct($mst = '')
    {
        $this->mst = $mst;
        $this->xrf = $mst;
        $this->cnt = $mst;
        $this->l01 = $mst;
        $this->l02 = $mst;
        $this->n01 = $mst;
        $this->n02 = $mst;
        $this->ifp = $mst;
        $this->any = $mst;
        $this->pft = $mst;
        $this->ext = $mst;
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Ответ сервера.
     * @throws IrbisException
     */
    public function parse(array $lines)
    {
        $map = array();
        foreach ($lines as $line) {
            if (is_null_or_empty($line)) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) != 2) {
                throw new IrbisException();
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $map[$key] = $value;
        }

        $this->xrf = $map['1'];
        $this->mst = $map['2'];
        $this->cnt = $map['3'];
        $this->n01 = $map['4'];
        $this->n02 = $map['5'];
        $this->l01 = $map['6'];
        $this->l02 = $map['7'];
        $this->ifp = $map['8'];
        $this->any = $map['9'];
        $this->pft = $map['10'];
        $this->ext = $map['11'];
    } // function parse

    public function __toString()
    {
        return '1=' . $this->xrf . PHP_EOL
            . '2=' . $this->mst . PHP_EOL
            . '3=' . $this->cnt . PHP_EOL
            . '4=' . $this->n01 . PHP_EOL
            . '5=' . $this->n02 . PHP_EOL
            . '6=' . $this->l01 . PHP_EOL
            . '7=' . $this->l02 . PHP_EOL
            . '8=' . $this->ifp . PHP_EOL
            . '9=' . $this->any . PHP_EOL
            . '10=' . $this->pft . PHP_EOL
            . '11=' . $this->ext . PHP_EOL;
    } // function __toString()

} // class ParFile

/**
 * Строка OPT-файла.
 */
final class OptLine
{
    /**
     * @var string Паттерн.
     */
    public $pattern = '';

    /**
     * @var string Соответствующий рабочий лист.
     */
    public $worksheet = '';

    /**
     * @param $text
     * @throws IrbisException
     */
    public function parse($text)
    {
        $parts = preg_split("/\s+/", trim($text), 2, PREG_SPLIT_NO_EMPTY);
        if (count($parts) != 2) {
            throw new IrbisException();
        }

        $this->pattern = $parts[0];
        $this->worksheet = $parts[1];
    } // function parse

    public function __toString()
    {
        return $this->pattern . ' ' . $this->worksheet;
    } // function __toString

} // class OptLine

/**
 * OPT-файл -- файл оптимизации рабочих листов и форматов показа.
 */
final class OptFile
{
    // Пример OPT-файла
    //
    // 920
    // 5
    // PAZK  PAZK42
    // PVK   PVK42
    // SPEC  SPEC42
    // J     !RPJ51
    // NJ    !NJ31
    // NJP   !NJ31
    // NJK   !NJ31
    // AUNTD AUNTD42
    // ASP   ASP42
    // MUSP  MUSP
    // SZPRF SZPRF
    // BOUNI BOUNI
    // IBIS  IBIS
    // +++++ PAZK42
    // *****

    /**
     * @var int Длина рабочего листа.
     */
    public $worksheetLength = 5;

    /**
     * @var int Метка поля рабочего листа.
     */
    public $worksheetTag = 920;

    /**
     * @var array Строки с паттернами.
     */
    public $lines = array();

    /**
     * Получение рабочего листа записи.
     *
     * @param MarcRecord $record Запись
     * @return string Рабочий лист.
     */
    public function getWorksheet(MarcRecord $record)
    {
        return $record->fm($this->worksheetTag);
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки OPT-файла.
     * @throws IrbisException
     */
    public function parse(array $lines)
    {
        if (empty($lines) || count($lines) < 2)
            throw new IrbisException();

        $this->worksheetTag = intval($lines[0]);
        $this->worksheetLength = intval($lines[1]);
        $lines = array_slice($lines, 2);
        foreach ($lines as $line) {
            if (is_null_or_empty($line)) {
                continue;
            }

            if ($line[0] === '*') {
                break;
            }

            $item = new OptLine();
            $item->parse($line);
            $this->lines[] = $item;
        }
    }

    public static function sameChar($pattern, $testable)
    {
        if ($pattern == '+') {
            return true;
        }

        return strtolower($pattern) == strtolower($testable);
    }

    /**
     * Сопоставление строки с OPT-шаблоном.
     *
     * @param string $pattern Шаблон.
     * @param string $testable Проверяемая строка.
     * @return bool Совпало?
     */
    public static function sameText($pattern, $testable)
    {
        if (!$pattern) {
            return false;
        }

        if (!$testable) {
            return $pattern[0] == '+';
        }

        $patternIndex = 0;
        $testableIndex = 0;
        while (true) {
            $patternChar = $patternIndex < strlen($pattern)
                ? $pattern[$patternIndex] : "\0";
            $testableChar = $testableIndex < strlen($testable)
                ? $testable[$testableIndex] : "\0";
            $patternNext = $patternIndex++ < strlen($pattern);
            $testableNext = $testableIndex++ < strlen($testable);

            if ($patternNext && !$testableNext) {
                if ($patternChar == '+') {
                    while ($patternIndex < strlen($pattern)) {
                        $patternChar = $pattern[$patternIndex];
                        $patternIndex++;
                        if ($patternChar != '+') {
                            return false;
                        }
                    }

                    return true;
                }
            }

            if ($patternNext != $testableNext) {
                return false;
            }

            if (!$patternNext) {
                return true;
            }

            if (!self::sameChar($patternChar, $testableChar)) {
                return false;
            }
        }

        return false; // for PhpStorm
    }

    /**
     * Подбор значения для указанного текста.
     *
     * @param string $text Проверяемый текст.
     * @return string|null Найденное значение либо null.
     */
    public function resolveWorksheet($text)
    {
        foreach ($this->lines as $line) {
            if (self::sameText($line->pattern, $text)) {
                return $line->worksheet;
            }
        }

        return null;
    }

    public function __toString()
    {
        $result = strval($this->worksheetTag) . PHP_EOL
            . strval($this->worksheetLength) . PHP_EOL;

        foreach ($this->lines as $line) {
            $result .= (strval($line) . PHP_EOL);
        }

        $result .= '*****' . PHP_EOL;

        return $result;
    }

} // class OptFile

final class GblParameter
{
    /**
     * @var string Наименование параметра, которое появится
     * в названии столбца, задающего параметр.
     */
    public $title = '';

    /**
     * @var string Значение параметра или пусто, если пользователю
     * предлагается задать его значение перед выполнением
     * корректировки. В этой строке можно задать имя файла
     * меню (с расширением MNU) или имя рабочего листа подполей
     * (с расширением Wss), которые будут поданы для выбора
     * значения параметра.
     */
    public $value = '';

} // class GblParameter

/**
 * Оператор глобальной корректировки с параметрами.
 */
final class GblStatement
{
    /**
     * @var string Команда, например, ADD или DEL.
     */
    public $command = '';

    /**
     * @var string Первый параметр, как правило, спецификация поля/подполя.
     */
    public $parameter1 = '';

    /**
     * @var string Второй параметр, как правило, спецификация повторения.
     */
    public $parameter2 = '';

    /**
     * @var string Первый формат, например, выражение для замены.
     */
    public $format1 = '';

    /**
     * @var string Второй формат, например, заменяющее выражение.
     */
    public $format2 = '';

    /**
     * GblStatement constructor.
     *
     * @param string $command Команда.
     * @param string $parameter1 Параметр 1.
     * @param string $parameter2 Параметр 2.
     * @param string $format1 Формат 1.
     * @param string $format2 Формат 2.
     */
    public function __construct($command,
                                $parameter1 = 'XXXXXXXXX',
                                $parameter2 = 'XXXXXXXXX',
                                $format1 = 'XXXXXXXXX',
                                $format2 = 'XXXXXXXXX')
    {
        $this->command = $command;
        $this->parameter1 = $parameter1;
        $this->parameter2 = $parameter2;
        $this->format1 = $format1;
        $this->format2 = $format2;
    }

    public function __toString()
    {
        return $this->command . IRBIS_DELIMITER
            . $this->parameter1 . IRBIS_DELIMITER
            . $this->parameter2 . IRBIS_DELIMITER
            . $this->format1 . IRBIS_DELIMITER
            . $this->format2 . IRBIS_DELIMITER;
    }

} // class GblStatement

/**
 * Настройки для глобальной корректировки.
 */
final class GblSettings
{
    /**
     * @var bool Актуализировать записи?
     */
    public $actualize = true;

    /**
     * @var bool Запускать autoin.gbl?
     */
    public $autoin = false;

    /**
     * @var string Имя базы данных.
     */
    public $database = '';

    /**
     * @var string Имя файла.
     */
    public $filename = '';

    /**
     * @var bool Применять формальный контроль?
     */
    public $formalControl = false;

    /**
     * @var int Нижняя граница MFN для поиска обрабатываемых записей.
     */
    public $lowerBound = 0;

    /**
     * @var int Максимальный MFN.
     */
    public $maxMfn = 0;

    /**
     * @var array Список MFN для обработки.
     */
    public $mfnList = array();

    /**
     * @var int Минимальный MFN. 0 означает "все записи в базе".
     */
    public $minMfn = 0;

    /**
     * @var array Параметры глобальной корректировки.
     * Как правило, параметров нет.
     */
    public $parameters = array();

    /**
     * @var string Поисковое выражение отбора записей по словарю.
     */
    public $searchExpression = '';

    /**
     * @var string Поисковое выражение последовательного поиска.
     */
    public $sequentialExpression = '';

    /**
     * @var array Массив операторов.
     */
    public $statements = array();

    /**
     * @var int Верхняя граница MFN для поиска обрабатываемых записей.
     */
    public $upperBound = 0;

    /**
     * Произвести подстановку параметров (если таковые наличествуют).
     *
     * @param $text string Текст, в котором должна быть произведена подстановка.
     * @return string Текст после подстановок.
     */
    public function substituteParameters($text)
    {
        $length = count($this->parameters);
       for ($i = 0; $i < $length; $i += 1) {
           $mark = '%' . strval($i + 1);
           $text = str_replace($mark, $this->parameters[$i]->value, $text);
       }

       return $text;
    }

} // class GblSettings

/**
 * Клиентский запрос.
 */
final class ClientQuery
{
    private $accumulator = '';

    public function __construct(Connection $connection, $command)
    {
        $this->addAnsi($command)->newLine();
        $this->addAnsi($connection->workstation)->newLine();
        $this->addAnsi($command)->newLine();
        $this->add($connection->clientId)->newLine();
        $this->add($connection->queryId)->newLine();
        $this->addAnsi($connection->password)->newLine();
        $this->addAnsi($connection->username)->newLine();
        $this->newLine();
        $this->newLine();
        $this->newLine();
    }

    /**
     * Добавляем целое число
     * (по факту выходит кодировка ANSI).
     *
     * @param int $value Число.
     * @return $this
     */
    public function add($value)
    {
        $this->addAnsi(strval($value));

        return $this;
    }

    /**
     * Добавляем текст в кодировке ANSI.
     *
     * @param string $value Добавляемый текст.
     * @return $this
     */
    public function addAnsi($value)
    {
        $converted = utfToAnsi($value);
        $this->accumulator .= $converted;

        return $this;
    }

    /**
     * Добавляем формат. Кодировка UTF8.
     *
     * @param string $format Формат.
     * @return bool|ClientQuery
     */
    public function addFormat($format)
    {
        if (!$format) {
            $this->newLine();
            return false;
        }

        $prepared = prepare_format(ltrim($format));

        if ($format[0] === '@') {
            $this->addAnsi($format);
        } else if ($format[0] === '!') {
            $this->addUtf($prepared);
        } else {
            $this->addUtf("!" . $prepared);
        }

        return $this->newLine();
    }

    /**
     * Добавляем текст в кодировке UTF-8.
     *
     * @param string $value Добавляемый текст.
     * @return $this
     */
    public function addUtf($value)
    {
        $this->accumulator .= $value;

        return $this;
    }

    /**
     * Добавляем перевод строки.
     *
     * @return $this
     */
    public function newLine()
    {
        $this->accumulator .= chr(10);

        return $this;
    }

    public function __toString()
    {
        return strlen($this->accumulator) . chr(10) . $this->accumulator;
    }
} // class ClientQuery

/**
 * Ответ сервера.
 */
final class ServerResponse
{
    /**
     * @var string Код команды (дублирует запрос).
     */
    public $command = '';

    /**
     * @var int Идентификатор клиента (дублирует запрос).
     */
    public $clientId = 0;

    /**
     * @var int Номер команды (дублирует запрос).
     */
    public $queryId = 0;

    /**
     * @var int Код возврата (бывает не у всех ответов).
     */
    public $returnCode = 0;

    /**
     * @var int Размер ответа в байтах
     * (в некоторых сценариях не возвращается).
     */
    public $answerSize = 0;

    /**
     * @var string Версия сервера
     * (в некоторых сценариях не возвращается).
     */
    public $serverVersion = '';

    private $connection;
    private $answer;
    private $offset;
    private $answerLength;

    public function __construct(Connection $connection, $socket)
    {
        $this->connection = $connection;
        $this->answer = '';

        while ($buf = socket_read($socket, 2048)) {
            $this->answer .= $buf;
        }

        if ($connection->debug) {
            $this->debug();
        }

        $this->offset = 0;
        $this->answerLength = strlen($this->answer);

        $this->command = $this->readAnsi();
        $this->clientId = $this->readInteger();
        $this->queryId = $this->readInteger();
        $this->answerSize = $this->readInteger();
        $this->serverVersion = $this->readAnsi();
        for ($i = 0; $i < 5; $i++) {
            $this->readAnsi();
        }
    }

    /**
     * Проверка кода возврата.
     *
     * @param array $goodCodes Разрешенные коды возврата.
     * @return bool Результат проверки.
     */
    public function checkReturnCode(array $goodCodes = array())
    {
        if ($this->getReturnCode() < 0) {
            if (!in_array($this->returnCode, $goodCodes)) {
                $this->connection->lastError = $this->returnCode;
                return false;
            }
        }
        return true;
    }

    /**
     * Отладочная печать.
     */
    public function debug()
    {
        file_put_contents('php://stderr', print_r($this->answer, TRUE));
    }

    /**
     * Чтение строки без преобразования кодировок.
     *
     * @return string Прочитанная строка.
     */
    public function getLine()
    {
        $result = '';

        while ($this->offset < $this->answerLength) {
            $symbol = $this->answer[$this->offset];
            $this->offset++;

            if ($symbol == chr(13)) {
                if ($this->answer[$this->offset] == chr(10)) {
                    $this->offset++;
                }
                break;
            }

            $result .= $symbol;
        }

        return $result;
    }

    /**
     * Получение кода возврата.
     * Вызывается один раз в свой час и только тогда.
     * Отрицательное число свидетельствует о проблеме.
     *
     * @return int Код возврата.
     */
    public function getReturnCode()
    {
        $this->returnCode = $this->readInteger();
        return $this->returnCode;
    }

    /**
     * Чтение строки в кодировке ANSI.
     *
     * @return string Прочитанная строка.
     */
    public function readAnsi()
    {
        $result = $this->getLine();
        $result = ansiToUtf($result);

        return $result;
    }

    /**
     * Чтение целого числа.
     *
     * @return int Прочитанное число.
     */
    public function readInteger()
    {
        $line = $this->getLine();

        return intval($line);
    }

    /**
     * Чтение оставшихся строк в кодировке ANSI.
     *
     * @return array
     */
    public function readRemainingAnsiLines()
    {
        $result = array();

        while ($this->offset < $this->answerLength) {
            $line = $this->readAnsi();
            $result[] = $line;
        }

        return $result;
    }

    /**
     * Чтение оставшегося текста в кодировке ANSI.
     *
     * @return bool|string
     */
    public function readRemainingAnsiText()
    {
        $result = substr($this->answer, $this->offset);
        $this->offset = $this->answerLength;
        $result = ansiToUtf($result);

        return $result;
    }

    /**
     * Чтение оставшихся строк в кодировке UTF-8.
     *
     * @return array
     */
    public function readRemainingUtfLines()
    {
        $result = array();

        while ($this->offset < $this->answerLength) {
            $line = $this->readUtf();
            $result[] = $line;
        }

        return $result;
    }

    /**
     * Чтение оставшегося текста в кодировке UTF-8.
     *
     * @return bool|string
     */
    public function readRemainingUtfText()
    {
        $result = substr($this->answer, $this->offset);
        $this->offset = $this->answerLength;

        return $result;
    }

    /**
     * Чтение строки в кодировке UTF-8.
     *
     * @return string
     */
    public function readUtf()
    {
        return $this->getLine();
    }
} // class ServerResponse

/**
 * Подключение к ИРБИС-серверу.
 */
final class Connection
{
    /**
     * @var string Адрес сервера (можно как my.domain.com,
     * так и 192.168.1.1).
     */
    public $host = '127.0.0.1';

    /**
     * @var int Порт сервера.
     */
    public $port = 6666;

    /**
     * @var string Логин пользователя. Регистр символов не учитывается.
     */
    public $username = '';

    /**
     * @var string Пароль пользователя. Регистр символов учитывается.
     */
    public $password = '';

    /**
     * @var string Имя текущей базы данных.
     */
    public $database = 'IBIS';

    /**
     * @var string Код АРМа.
     */
    public $workstation = CATALOGER;

    /**
     * @var int Идентификатор клиента.
     * Задаётся автоматически при подключении к серверу.
     */
    public $clientId = 0;

    /**
     * @var int Последовательный номер запроса к серверу.
     * Ведётся автоматически.
     */
    public $queryId = 0;

    /**
     * @var string Версия сервера (доступна после подключения).
     */
    public $serverVersion = '';

    /**
     * @var IniFile Серверный INI-файл (доступен после подключения).
     */
    public $iniFile = null;

    /**
     * @var int Интервал подтверждения, минуты
     * (доступен после подключения).
     */
    public $interval = 0;

    private $connected = false;

    /**
     * @var bool Признак отладки.
     */
    public $debug = false;

    /**
     * @var int Код последней ошибки.
     */
    public $lastError = 0;

    //================================================================

    function __destruct()
    {
        $this->disconnect();
    }

    function _checkConnection()
    {
        if (!$this->connected) {
            $this->lastError = -100003;
            return false;
        }

        return true;
    }

    //================================================================

    /**
     * Актуализация всех неактуализированных записей
     * в указанной базе данных.
     *
     * @param string $database Имя базы данных.
     * @return bool Признак успешности операции.
     */
    public function actualizeDatabase($database)
    {
        return $this->actualizeRecord($database, 0);
    } // function actualizeDatabase

    /**
     * Актуализация записи с указанным MFN.
     *
     * @param string $database Имя базы данных.
     * @param int $mfn MFN, подлежащий актуализации.
     * @return bool Признак успешности операции.
     */
    public function actualizeRecord($database, $mfn)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'F');
        $query->addAnsi($database)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return true;
    } // function actualizeRecord

    /**
     * Подключение к серверу ИРБИС64.
     *
     * @return bool Признак успешности операции.
     */
    function connect()
    {
        if ($this->connected)
            return true;

        AGAIN:
        $this->clientId = rand(100000, 900000);
        $this->queryId = 1;
        $query = new ClientQuery($this, 'A');
        $query->addAnsi($this->username)->newLine();
        $query->addAnsi($this->password);

        $response = $this->execute($query);
        if (!$response)
            return false;

        $response->getReturnCode();
        if ($response->returnCode == -3337) {
            goto AGAIN;
        }

        if ($response->returnCode < 0) {
            $this->lastError = $response->returnCode;
            return false;
        }

        $this->connected = true;
        $this->serverVersion = $response->serverVersion;
        $this->interval = intval($response->readUtf());
        $lines = $response->readRemainingAnsiLines();
        $this->iniFile = new IniFile();
        $this->iniFile->parse($lines);

        return true;
    } // function connect

    /**
     * Создание базы данных.
     *
     * @param string $database Имя создаваемой базы.
     * @param string $description Описание в свободной форме.
     * @param int $readerAccess Читатель будет иметь доступ?
     * @return bool Признак успешности операции.
     */
    function createDatabase($database, $description, $readerAccess = 1)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'T');
        $query->addAnsi($database)->newLine();
        $query->addAnsi($description)->newLine();
        $query->add($readerAccess)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return true;
    } // function createDatabase

    /**
     * Создание словаря в указанной базе данных.
     *
     * @param string $database Имя базы данных.
     * @return bool Признак успешности операции.
     */
    public function createDictionary($database)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'Z');
        $query->addAnsi($database)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return true;
    } // function createDictionary

    /**
     * Удаление указанной базы данных.
     *
     * @param string $database Имя удаляемой базы данных.
     * @return bool Признак успешности операции.
     */
    public function deleteDatabase($database)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'W');
        $query->addAnsi($database)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return true;
    } // function deleteDatabase

    /**
     * Удаление на сервере указанного файла.
     *
     * @param string $fileName Спецификация файла.
     */
    public function deleteFile($fileName)
    {
        $this->formatRecord("&uf('+9K$fileName')", 1);
    } // function deleteFile

    /**
     * Удаление записи по её MFN.
     *
     * @param int $mfn MFN удаляемой записи.
     * @return bool Признак успешности операции.
     */
    public function deleteRecord($mfn)
    {
        $record = $this->readRecord($mfn);
        if (!$record)
            return false;

        if (!$record->isDeleted()) {
            $record->status |= LOGICALLY_DELETED;
            $this->writeRecord($record);
        }

        return true;
    } // function deleteRecord

    /**
     * Отключение от сервера.
     *
     * @return bool Признак успешности операции.
     */
    public function disconnect()
    {
        if (!$this->connected)
            return true;

        $query = new ClientQuery($this, 'B');
        $query->addAnsi($this->username);
        if (!$this->execute($query))
            return false;

        $this->connected = false;
        return true;
    } // function disconnect

    /**
     * Отправка клиентского запроса на сервер
     * и получение ответа от него.
     *
     * @param ClientQuery $query Клиентский запрос.
     * @return bool|ServerResponse Ответ сервера
     * либо признак сбоя операции.
     */
    public function execute(ClientQuery $query)
    {
        $this->lastError = 0;
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            $this->lastError = -100001;
            return false;
        }

        if (!socket_connect($socket, $this->host, $this->port)) {
            socket_close($socket);
            $this->lastError = -100002;
            return false;
        }

        $packet = strval($query);

        if ($this->debug) {
            file_put_contents('php://stderr', print_r($packet, TRUE));
        }

        socket_write($socket, $packet, strlen($packet));
        $response = new ServerResponse($this, $socket);
        $this->queryId++;

        return $response;
    } // function execute

    /**
     * Выполнение произвольной команды.
     *
     * @param string $command Код команды.
     * @param array $params Опциональные параметры в кодировке ANSI.
     * @return bool|ServerResponse Ответ сервера
     * либо признак сбоя операции.
     */
    public function executeAnyCommand($command, array $params = [])
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, $command);
        foreach ($params as $param)
            $query->addAnsi($param)->newLine();

        return $this->execute($query);
    } // function executeAnyCommand

    /**
     * Форматирование записи с указанным MFN.
     *
     * @param string $format Текст формата.
     * @param int $mfn MFN записи.
     * @return bool|string Результат расформатирования
     * либо признак сбоя операции.
     */
    public function formatRecord($format, $mfn)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'G');
        $query->addAnsi($this->database)->newLine();
        $query->addFormat($format);
        $query->add(1)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return $response->readRemainingUtfText();
    } // function formatRecord

    /**
     * Форматирование записи в клиентском представлении.
     *
     * @param string $format Текст формата.
     * @param MarcRecord $record Запись.
     * @return bool|string Результат расформатирования
     * либо признак сбоя операции.
     */
    public function formatVirtualRecord($format, MarcRecord $record)
    {
        if (!$this->_checkConnection())
            return false;

        if (!$record)
            return false;

        $query = new ClientQuery($this, 'G');
        $database = $record->database ?: $this->database;
        $query->addAnsi($database)->newLine();
        $query->addFormat($format);
        $query->add(-2)->newLine();
        $query->addUtf($record->encode());
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return $response->readRemainingUtfText();
    } // function formatVirtualRecord

    /**
     * Расформатирование нескольких записей.
     *
     * @param string $format Формат.
     * @param array $mfnList Массив MFN.
     * @return array|bool Результат расформатирования
     * либо признак сбоя операции.
     */
    public function formatRecords($format, array $mfnList)
    {
        if (!$this->_checkConnection())
            return false;

        if (!$mfnList)
            return array();

        $query = new ClientQuery($this, 'G');
        $query->addAnsi($this->database)->newLine();
        if (!$query->addFormat($format))
            return array();

        $query->add(count($mfnList))->newLine();
        foreach ($mfnList as $mfn)
            $query->add($mfn)->newLine();

        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        $lines = $response->readRemainingUtfLines();
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            if (count($parts) == 2)
                $result[] = irbis_to_dos($parts[1]);
        }

        return $result;
    } // function formatRecords

    /**
     * Получение информации о базе данных.
     *
     * @param string $database Имя базы данных.
     * @return bool|DatabaseInfo Информация о базе данных
     * либо признак сбоя операции.
     */
    public function getDatabaseInfo($database = '')
    {
        if (!$this->_checkConnection())
            return false;

        $database = $database ?: $this->database;
        $query = new ClientQuery($this, '0');
        $query->addAnsi($database);
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        $lines = $response->readRemainingAnsiLines();

        return DatabaseInfo::parseResponse($lines);
    } // function getDatabaseInfo

    /**
     * Получение максимального MFN для указанной базы данных.
     *
     * @param string $database Имя базы данных.
     * @return int Максимальный MFN
     * либо 0 в качестве признака сбоя операции.
     */
    public function getMaxMfn($database)
    {
        if (!$this->_checkConnection())
            return 0;

        $query = new ClientQuery($this, 'O');
        $query->addAnsi($database);
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return 0;

        return $response->returnCode;
    } // function getMaxMfn

    /**
     * Массив постингов для указанных записи и префикса.
     * @param int $mfn MFN записи.
     * @param string $prefix Префикс в виде "A=$".
     * @return array Массив TermPosting
     * (пустой в случае сбоя операции).
     */
    public function getRecordPostings($mfn, $prefix)
    {
        $result = array();
        if (!$this->_checkConnection())
            return $result;

        $query = new ClientQuery($this, 'V');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $query->addUtf($prefix)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return $result;

        $lines = $response->readRemainingUtfLines();
        return TermPosting::parse($lines);
    } // function getRecordPostings

    /**
     * Получение статистики с сервера.
     *
     * @return bool|ServerStat Статистика
     * либо признак сбоя операции.
     */
    public function getServerStat()
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '+1');
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        $result = new ServerStat();
        $result->parse($response->readRemainingAnsiLines());

        return $result;
    } // function getServerStat

    /**
     * Получение версии сервера.
     *
     * @return bool|VersionInfo Версия сервера
     * либо признак сбоя операции.
     */
    public function getServerVersion()
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '1');
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        $result = new VersionInfo();
        $result->parse($response->readRemainingAnsiLines());

        return $result;
    } // function getServerVersion

    /**
     * Получение списка пользователей с сервера.
     *
     * @return array|bool Список пользователей
     * либо признак сбоя операции.
     */
    public function getUserList()
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '+9');
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return UserInfo::parse($response->readRemainingAnsiLines());
    } // function getUserList

    /**
     * Глобальная корректировка.
     *
     * @param GblSettings $settings Параметры корректировки.
     * @return array|bool Массив результатов корректировки
     * либо признак сбоя операции.
     */
    public function globalCorrection(GblSettings $settings)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '5');
        $database = $settings->database ?: $this->database;
        $query->addAnsi($database)->newLine();
        $query->add(intval($settings->actualize))->newLine();
        if (!is_null_or_empty($settings->filename)) {
            $query->addAnsi('@' . $settings->filename)->newLine();
        } else {
            // "!" здесь означает, что передавать будем в UTF-8
            // не знаю, что тут означает "0"
            $encoded = '!0' . IRBIS_DELIMITER;
            foreach ($settings->statements as $statement) {
                $encoded .=  $settings->substituteParameters(strval($statement));
            }
            $encoded .= IRBIS_DELIMITER;
            $query->addUtf($encoded)->newLine();
        }

        // отбор записей на основе поиска
        $query->addUtf($settings->searchExpression)->newLine(); // поиск по словарю
        $query->add($settings->lowerBound)->newLine(); // нижняя граница MFN
        $query->add($settings->upperBound)->newLine(); // верхняя граница MFN
        $query->addUtf($settings->sequentialExpression)->newLine(); // последовательный

        // TODO поддержка режима "кроме отмеченных"
        if (!$settings->mfnList) {
            $count = $settings->maxMfn - $settings->minMfn + 1;
            $query->add($count)->newLine();
            for ($mfn = $settings->minMfn; $mfn < $settings->maxMfn; $mfn++) {
                $query->add($mfn)->newLine();
            }
        } else {
            $query->add(count($settings->mfnList))->newLine();
            foreach ($settings->mfnList as $item) {
                $query->add($item)->newLine();
            }
        }

        if (!$settings->formalControl)
            $query->addAnsi('*')->newLine();

        if (!$settings->autoin)
            $query->addAnsi('&')->newLine();

        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return $response->readRemainingAnsiLines();
    } // function globalCorrection

    /**
     * @return bool Получение статуса,
     * подключен ли клиент в настоящее время.
     */
    public function isConnected()
    {
        return $this->connected;
    } // function isConnected

    /**
     * Получение списка баз данных с сервера.
     *
     * @param string $specification Спецификация файла со списком баз.
     * @return array|bool Список баз данных
     * либо признак сбоя операции.
     */
    public function listDatabases($specification = '1..dbnam2.mnu')
    {
        if (!$this->_checkConnection())
            return false;

        $menu = $this->readMenuFile($specification);
        if (!$menu)
            return false;

        return DatabaseInfo::parseMenu($menu);
    } // function listDatabases

    /**
     * Получение списка файлов.
     *
     * @param string $specification Спецификация.
     * @return array|bool Список файлов
     * либо признак сбоя операции.
     */
    public function listFiles($specification)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '!');
        $query->addAnsi($specification)->newLine();
        $response = $this->execute($query);
        if (!$response)
            return false;

        $lines = $response->readRemainingAnsiLines();
        $result = array();
        foreach ($lines as $line) {
            $files = irbis_to_lines($line);
            foreach ($files as $file) {
                if (!is_null_or_empty($file)) {
                    $result[] = $file;
                }
            }
        }

        return $result;
    } // function listFiles

    /**
     * Получение списка серверных процессов.
     *
     * @return array|bool Список процессов
     * либо признак сбоя операции.
     */
    public function listProcesses()
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '+3');
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        $lines = $response->readRemainingAnsiLines();

        return ProcessInfo::parse($lines);
    } // function listProcesses

    /**
     * Получение списка терминов с указанным префиксом.
     *
     * @param string $prefix Префикс.
     * @return array Термины (очищенные от префикса)
     * (пустой массив при сбое операции).
     */
    public function listTerms($prefix)
    {
        $result = array();

        if (!$this->_checkConnection())
            return $result;

        $prefixLength = strlen($prefix);
        $startTerm = $prefix;
        $lastTerm = $startTerm;
        while (true) {
            $terms = $this->readTerms($startTerm, 512);
            if (!$terms)
                break;

            foreach ($terms as $term) {
                $text = $term->text;
                if (strcmp(substr($text, 0, $prefixLength), $prefix)) {
                    break 2;
                }
                if ($text !== $startTerm) {
                    $lastTerm = $text;
                    $text = substr($text, $prefixLength);
                    $result[] = $text;
                }
            }
            $startTerm = $lastTerm;
        }

        return $result;
    } // function listTerms

    /**
     * Пустая операция (используется для периодического
     * подтверждения подключения клиента).
     *
     * @return bool Всегда true при наличии подключения,
     * т. к. код возврата не анализируется.
     * Всегда false при отсутствии подключения.
     */
    public function noOp()
    {
        if (!$this->_checkConnection()) {
            return false;
        }

        $query = new ClientQuery($this, 'N');
        if (!$this->execute($query))
            return false;

        return true;
    } // function noOp

    /**
     * Разбор строки подключения.
     *
     * @param string $connectionString Строка подключения.
     * @throws IrbisException Ошибка в структуре строки подключения.
     */
    public function parseConnectionString($connectionString)
    {
        $items = explode(';', $connectionString);
        foreach ($items as $item) {
            if (is_null_or_empty($item)) {
                continue;
            }

            $parts = explode('=', $item, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            switch ($name) {
                case 'host':
                case 'server':
                case 'address':
                    $this->host = $value;
                    break;

                case 'port':
                    $this->port = intval($value);
                    break;

                case 'user':
                case 'username':
                case 'name':
                case 'login':
                    $this->username = $value;
                    break;

                case 'pwd':
                case 'password':
                    $this->password = $value;
                    break;

                case 'db':
                case 'database':
                case 'catalog':
                    $this->database = $value;
                    break;

                case 'arm':
                case 'workstation':
                    $this->workstation = $value;
                    break;

                case 'debug':
                    $this->debug = $value;
                    break;

                default:
                    throw new IrbisException("Unknown key $name");
            }
        }
    } // function parseConnectionString

    /**
     * Расформатирование таблицы.
     *
     * @param TableDefinition $definition Определение таблицы.
     * @return bool|string Результат расформатирования
     * либо признак сбоя операции.
     */
    public function printTable(TableDefinition $definition)
    {
        if (!$this->_checkConnection())
            return false;

        $database = $definition->database ?: $this->database;
        $query = new ClientQuery($this, '7');
        $query->addAnsi($database)->newLine();
        $query->addAnsi($definition->table)->newLine();
        $query->addAnsi('')->newLine(); // вместо заголовков
        $query->addAnsi($definition->mode)->newLine();
        $query->addAnsi($definition->searchQuery)->newLine();
        $query->add($definition->minMfn)->newLine();
        $query->add($definition->maxMfn)->newLine();
        $query->addUtf($definition->sequentialQuery)->newLine();
        $query->addAnsi(''); // вместо перечня MFN
        $response = $this->execute($query);
        if (!$response)
            return false;

        return $response->readRemainingUtfText();
    } // function printTable

    /**
     * Получение INI-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return IniFile|null INI-файл
     * либо null в качестве признака сбоя операции.
     */
    public function readIniFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            return null;

        $result = new IniFile();
        $result->parse($lines);

        return $result;
    } // function readIniFile

    /**
     * Чтение MNU-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|MenuFile MNU-файл
     * либо признак сбоя операции.
     */
    public function readMenuFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            return false;

        $result = new MenuFile();
        $result->parse($lines);

        return $result;
    } // function readMenuFile

    /**
     * Чтение OPT-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|OptFile OPT-файл
     * либо признак сбоя операции.
     * @throws IrbisException Ошибка в структуре OPT-файла.
     */
    public function readOptFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            return false;

        $result = new OptFile();
        $result->parse($lines);

        return $result;
    } // function readOptFile

    /**
     * Чтение PAR-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|ParFile PAR-файл
     * либо признак сбоя операции.
     * @throws IrbisException Ошибка в структуре PAR-файла.
     */
    public function readParFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            return false;

        $result = new ParFile();
        $result->parse($lines);

        return $result;
    } // function readParFile

    /**
     * Считывание постингов из поискового индекса.
     *
     * @param PostingParameters $parameters Параметры постингов.
     * @return array|bool Массив постингов
     * либо признак сбоя операции.
     */
    public function readPostings(PostingParameters $parameters)
    {
        if (!$this->_checkConnection())
            return false;

        $database = $parameters->database ?: $this->database;
        $query = new ClientQuery($this, 'I');
        $query->addAnsi($database)->newLine();
        $query->add($parameters->numberOfPostings)->newLine();
        $query->add($parameters->firstPosting)->newLine();
        $query->addFormat($parameters->format);
        if (!$parameters->listOfTerms) {
            $query->addUtf($parameters->term)->newLine();
        } else {
            foreach ($parameters->listOfTerms as $term) {
                $query->addUtf($term)->newLine();
            }
        }

        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode(codes_for_read_terms()))
            return false;

        $lines = $response->readRemainingUtfLines();

        return TermPosting::parse($lines);
    } // function readPostings

    /**
     * Чтение указанной записи в "сыром" виде.
     *
     * @param string $mfn MFN записи
     * @return bool|RawRecord Запись
     * либо признак сбоя операции.
     */
    public function readRawRecord($mfn)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'C');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode(codes_for_read_record()))
            return false;

        $result = new RawRecord();
        $result->decode($response->readRemainingUtfLines());
        $result->database = $this->database;

        return $result;
    } // function readRawRecord

    /**
     * Чтение указанной записи.
     *
     * @param int $mfn MFN записи
     * @return bool|MarcRecord Запись
     * либо признак сбоя операции.
     */
    public function readRecord($mfn)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'C');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode(codes_for_read_record()))
            return false;

        $result = new MarcRecord();
        $result->decode($response->readRemainingUtfLines());
        $result->database = $this->database;

        return $result;
    } // function readRecord

    /**
     * Чтение указанной версии записи.
     *
     * @param int $mfn MFN записи
     * @param int $version Версия записи
     * @return bool|MarcRecord Запись
     * либо признак сбоя операции.
     */
    public function readRecordVersion($mfn, $version)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'C');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $query->add($version);
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode(codes_for_read_record()))
            return false;

        $result = new MarcRecord();
        $result->decode($response->readRemainingUtfLines());
        $result->database = $this->database;

        return $result;
    } // function readRecordVersion

    /**
     * Чтение с сервера нескольких записей.
     *
     * @param array $mfnList Массив MFN.
     * @return array Массив записей
     * (пустой массив как признак сбоя операции).
     */
    public function readRecords(array $mfnList)
    {
        if (!$this->_checkConnection())
            return array();

        if (!$mfnList) {
            return array();
        }

        if (count($mfnList) == 1) {
            $result = array();
            $record = $this->readRecord($mfnList[0]);
            if ($record) {
                $result[] = $record;
            }
            return $result;
        }

        $query = new ClientQuery($this, 'G');
        $query->addAnsi($this->database)->newLine();
        $query->addAnsi(ALL_FORMAT)->newLine();
        $query->add(count($mfnList))->newLine();
        foreach ($mfnList as $mfn) {
            $query->add($mfn)->newLine();
        }
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return array();

        $lines = $response->readRemainingUtfLines();
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            if (count($parts) > 1) {
                $parts = explode("\x1F", $parts[1]);
                $parts = array_slice($parts, 1);
                $record = new MarcRecord();
                $record->decode($parts);
                $record->database = $this->database;
                $result[] = $record;
            }
        }

        return $result;
    } // function readRecords

    /**
     * Загрузка сценариев поиска с сервера.
     *
     * @param string $specification Спецификация.
     * @return array|bool Массив сценариев
     * либо признак сбоя операции.
     */
    public function readSearchScenario($specification)
    {
        if (!$this->_checkConnection())
            return false;

        $iniFile = $this->readIniFile($specification);
        if (!$iniFile)
            return false;

        return SearchScenario::parse($iniFile);
    } // function readSearchScenario

    /**
     * Простое получение терминов поискового словаря.
     *
     * @param string $startTerm Начальный термин.
     * @param int $numberOfTerms Необходимое количество терминов.
     * @return array|bool Массив терминов
     * либо призак сбоя операции.
     */
    public function readTerms($startTerm, $numberOfTerms = 100)
    {
        $parameters = new TermParameters();
        $parameters->startTerm = $startTerm;
        $parameters->numberOfTerms = $numberOfTerms;

        return $this->readTermsEx($parameters);
    } // function readTerms

    /**
     * Получение терминов поискового словаря.
     *
     * @param TermParameters $parameters Параметры терминов.
     * @return array|bool Массив терминов
     * либо признак сбоя операции.
     */
    public function readTermsEx(TermParameters $parameters)
    {
        if (!$this->_checkConnection())
            return false;

        $command = $parameters->reverseOrder ? 'P' : 'H';
        $database = $parameters->database ?: $this->database;
        $query = new ClientQuery($this, $command);
        $query->addAnsi($database)->newLine();
        $query->addUtf($parameters->startTerm)->newLine();
        $query->add($parameters->numberOfTerms)->newLine();
        $query->addFormat($parameters->format);
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode(codes_for_read_terms()))
            return false;

        $lines = $response->readRemainingUtfLines();

        return TermInfo::parse($lines);
    } // function readTermsEx

    /**
     * Получение текстового файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|string Текст файла
     * либо признак сбоя операции.
     */
    public function readTextFile($specification)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'L');
        $query->addAnsi($specification)->newLine();
        $response = $this->execute($query);
        if (!$response)
            return false;

        $result = $response->readAnsi();
        $result = irbis_to_dos($result);

        return $result;
    } // function readTextFile

    /**
     * Получение текстового файла в виде массива строк.
     *
     * @param string $specification Спецификация файла.
     * @return array Массив строк
     * (пустой массив как признак сбоя операции).
     */
    public function readTextLines($specification)
    {
        if (!$this->_checkConnection())
            return array();

        $query = new ClientQuery($this, 'L');
        $query->addAnsi($specification)->newLine();
        $response = $this->execute($query);
        if (!$response)
            return array();

        $result = $response->readAnsi();
        $result = irbis_to_lines($result);

        return $result;
    } // function readTextLines

    /**
     * Чтение TRE-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|TreeFile TRE-файл
     * либо признак сбоя операции.
     * @throws IrbisException Ошибка в структуре TRE-файла.
     */
    public function readTreeFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            return false;

        $result = new TreeFile();
        $result->parse($lines);

        return $result;
    } // function readTreeFile

    /**
     * Пересоздание словаря для указанной базы данных.
     *
     * @param string $database База данных.
     * @return bool Признак успешности операции.
     */
    public function reloadDictionary($database)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'Y');
        $query->addAnsi($database)->newLine();
        if (!$this->execute($query))
            return false;

        return true;
    } // function reloadDictionary

    /**
     * Пересоздание мастер-файла для указанной базы данных.
     *
     * @param string $database База данных.
     * @return bool Признак успешности операции.
     */
    public function reloadMasterFile($database)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'X');
        $query->addAnsi($database)->newLine();
        if (!$this->execute($query))
            return false;

        return true;
    } // function reloadMasterFile

    /**
     * Получение INI-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return IniFile Полученный INI-файл.
     * @throws IrbisException Файл не найден.
     */
    public function requireIniFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            throw new IrbisException("File not found: " . $specification);

        $result = new IniFile();
        $result->parse($lines);

        return $result;
    } // function requireIniFile

    /**
     * Получение MNU-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return MenuFile Полученный MNU-файл.
     * @throws IrbisException Файл не найден.
     */
    public function requireMenuFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            throw new IrbisException("File not found: " . $specification);

        $result = new MenuFile();
        $result->parse($lines);

        return $result;
    } // function requireMenuFile

    /**
     * Получение OPT-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return OptFile Полученный OPT-файл.
     * @throws IrbisException Файл не найден.
     */
    public function requireOptFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            throw new IrbisException("File not found: " . $specification);

        $result = new OptFile();
        $result->parse($lines);

        return $result;
    } // function requireOptFile

    /**
     * Получение PAR-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return ParFile Полученный PAR-файл.
     * @throws IrbisException Файл не найден.
     */
    public function requireParFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            throw new IrbisException("File not found: " . $specification);

        $result = new ParFile();
        $result->parse($lines);

        return $result;
    } // function requireParFile

    /**
     * Получение текстового файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return string Текст полученного файла.
     * @throws IrbisException Файл не найден.
     */
    public function requireTextFile($specification)
    {
        $result = $this->readTextFile($specification);
        if (!$result || is_null_or_empty($result))
            throw new IrbisException("File not found: " . $specification);

        return $result;
    } // function requireTextFile

    /**
     * Получение TRE-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return TreeFile Полученный TRE-файл.
     * @throws IrbisException Файл не найден.
     */
    public function requireTreeFile($specification)
    {
        $lines = $this->readTextLines($specification);
        if (!$lines)
            throw new IrbisException("File not found: " . $specification);

        $result = new TreeFile();
        $result->parse($lines);

        return $result;
    } // function requireTreeFile

    /**
     * Перезапуск сервера (без утери подключенных клиентов).
     *
     * @return bool Признак успешности операции.
     */
    public function restartServer()
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '+8');
        if (!$this->execute($query))
            return false;

        return true;
    } // function restartServer

    /**
     * Простой поиск записей (не более 32 тыс. записей).
     *
     * @param string $expression Выражение для поиска по словарю.
     * @return array|bool Массив найденных MFN
     * либо признак сбоя операции.
     */
    public function search($expression)
    {
        $parameters = new SearchParameters();
        $parameters->expression = $expression;
        $found = $this->searchEx($parameters);

        return FoundLine::toMfn($found);
    } // function search

    /**
     * Поиск всех записей (даже если их окажется больше 32 тыс.).
     *
     * @param string $expression Выражение для поиска по словарю.
     * @return array Массив MFN найденных записей
     * (возможно, пустой).
     */
    public function searchAll($expression)
    {
        $result = array();
        if (!$this->_checkConnection())
            return $result;

        $firstRecord = 1;
        $totalCount = 0;

        while (true) {
            $query = new ClientQuery($this, 'K');
            $query->addAnsi($this->database)->newLine();
            $query->addUtf((string)$expression)->newLine();
            $query->add(0)->newLine();
            $query->add($firstRecord)->newLine();
            $response = $this->execute($query);
            if (!$response || !$response->checkReturnCode())
                return $result; // TODO реагировать правильно

            if ($firstRecord == 1) {
                $totalCount = $response->readInteger();
                if (!$totalCount) {
                    break;
                }
            } else {
                $response->readInteger(); // Eat the line
            }

            $lines = $response->readRemainingUtfLines();
            $found = FoundLine::parseMfn($lines);
            if (!$found)
                break;

            $result = $result + $found;
            $firstRecord += count($found);
            if ($firstRecord >= $totalCount)
                break;
        } // while

        return $result;
    } // function searchAll

    /**
     * Определение количества записей,
     * соответствующих поисковому выражению.
     *
     * @param string $expression Поисковое выражение.
     * @return int Количество соответствующих записей.
     */
    public function searchCount($expression)
    {
        if (!$this->_checkConnection())
            return 0;

        $query = new ClientQuery($this, 'K');
        $query->addAnsi($this->database)->newLine();
        $query->addUtf((string)$expression)->newLine();
        $query->add(0)->newLine();
        $query->add(0);
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return $response->readInteger(); // Число найденных записей
    } // function searchCount

    /**
     * Расширенный поиск записей.
     *
     * @param SearchParameters $parameters Параметры поиска.
     * @return array|bool Массив найденных записей
     * либо признак сбоя операции.
     */
    public function searchEx(SearchParameters $parameters)
    {
        if (!$this->_checkConnection())
            return false;

        $database = $parameters->database ?: $this->database;
        $query = new ClientQuery($this, 'K');
        $query->addAnsi($database)->newLine();
        $query->addUtf((string)($parameters->expression))->newLine();
        $query->add($parameters->numberOfRecords)->newLine();
        $query->add($parameters->firstRecord)->newLine();
        $query->addFormat($parameters->format);
        $query->add($parameters->minMfn)->newLine();
        $query->add($parameters->maxMfn)->newLine();
        $query->addAnsi($parameters->sequential)->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        $response->readInteger(); // Число найденных записей.
        $lines = $response->readRemainingUtfLines();
        $result = FoundLine::parse($lines);

        return $result;
    } // function searchEx

    /**
     * Поиск записей с их одновременным считыванием.
     *
     * @param string $expression Поисковое выражение.
     * @param int $limit Максимальное количество загружаемых записей.
     * @return array Массив полученных записей
     * (возможно, пустой).
     */
    public function searchRead($expression, $limit = 0)
    {
        $parameters = new SearchParameters();
        $parameters->expression = $expression;
        $parameters->format = ALL_FORMAT;
        $parameters->numberOfRecords = $limit;
        $found = $this->searchEx($parameters);
        if (!$found)
            return array();

        $result = array();
        foreach ($found as $item) {
            $lines = explode("\x1F", $item->description);
            $lines = array_slice($lines, 1);
            $record = new MarcRecord();
            $record->decode($lines);
            $record->database = $this->database;
            $result[] = $record;
        }

        return $result;
    } // function searchRead

    /**
     * Поиск и считывание одной записи, соответствующей выражению.
     * Если таких записей больше одной, то будет считана любая из них.
     * Если таких записей нет, будет возвращен null.
     *
     * @param string $expression Поисковое выражение.
     * @return MarcRecord|null Полученная запись либо null,
     * если запись не найдена.
     */
    public function searchSingleRecord($expression)
    {
        $found = $this->searchRead($expression, 1);
        if (count($found))
            return $found[0];

        return null;
    } // function searchSingleRecord

    /**
     * Бросает исключение, если произошла ошибка
     * при выполнении последней операции.
     * @throws IrbisException Обнаружена ошибка,
     * выброшено исключение.
     */
    public function throwOnError()
    {
        if ($this->lastError < 0)
            throw new IrbisException($this->lastError);
    } // function throwOnError

    /**
     * Выдача строки подключения для текущего соединения.
     * Соединение не обязательно должно быть установлено.
     *
     * @return string Строка подключения для текушего соединения
     * (не обязательно активного).
     */
    public function toConnectionString()
    {
        return 'host=' . $this->host
            . ';port=' . $this->port
            . ';username=' . $this->username
            . ';password=' . $this->password
            . ';database=' . $this->database
            . ';arm=' . $this->workstation . ';';
    } // function toConnectionString

    /**
     * Опустошение указанной базы данных.
     *
     * @param string $database База данных.
     * @return bool Признак успешности операции.
     */
    public function truncateDatabase($database)
    {
        if (!$this->_checkConnection()) {
            return false;
        }

        $query = new ClientQuery($this, 'S');
        $query->addAnsi($database)->newLine();
        if (!$this->execute($query))
            return false;

        return true;
    } // function truncateDatabase

    /**
     * Восстановление записи по её MFN.
     *
     * @param int $mfn MFN восстанавливаемой записи.
     * @return bool|MarcRecord Восстановленная запись
     * либо признак сбоя операции.
     */
    public function undeleteRecord($mfn)
    {
        $record = $this->readRecord($mfn);
        if (!$record)
            return $record;

        if ($record->isDeleted()) {
            $record->status &= ~LOGICALLY_DELETED;
            if (!$this->writeRecord($record))
                return false;
        }

        return $record;
    } // function undeleteRecord

    /**
     * Разблокирование указанной базы данных.
     *
     * @param string $database База данных.
     * @return bool Признак успешности операции.
     */
    public function unlockDatabase($database)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'U');
        $query->addAnsi($database)->newLine();
        if (!$this->execute($query))
            return false;

        return true;
    } // function unlockDatabase

    /**
     * Разблокирование записей.
     *
     * @param string $database База данных.
     * @param array $mfnList Массив MFN.
     * @return bool Признак успешности операции.
     */
    public function unlockRecords($database, array $mfnList)
    {
        if (!$this->_checkConnection())
            return false;

        if (count($mfnList) == 0)
            return true;

        $database = $database ?: $this->database;
        $query = new ClientQuery($this, 'Q');
        $query->addAnsi($database)->newLine();
        foreach ($mfnList as $mfn)
            $query->add($mfn)->newLine();

        if (!$this->execute($query))
            return false;

        return true;
    } // function unlockRecords

    /**
     * Обновление строк серверного INI-файла
     * для текущего пользователя.
     *
     * @param array $lines Изменённые строки.
     * @return bool Признак успешности операции.
     */
    public function updateIniFile(array $lines)
    {
        if (!$this->_checkConnection())
            return false;

        if (!$lines)
            return true;

        $query = new ClientQuery($this, '8');
        foreach ($lines as $line)
            $query->addAnsi($line)->newLine();

        if (!$this->execute($query))
            return false;

        return true;
    } // function updateIniFile

    /**
     * Обновление списка пользователей на сервере.
     *
     * @param array $users Список пользователей.
     * @return bool Признак успешности операции.
     */
    public function updateUserList(array $users)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, '+7');
        foreach ($users as $user)
            $query->addAnsi($user->encode())->newLine();
        if (!$this->execute($query))
            return false;

        return true;
    } // function updateUserList

    /**
     * Сохранение на сервере "сырой" записи.
     *
     * @param RawRecord $record Запись для сохранения.
     * @return bool|int Новый максимальный MFN в базе данных
     * либо признак сбоя операции.
     */
    public function writeRawRecord(RawRecord $record)
    {
        if (!$this->_checkConnection())
            return false;

        $database = $record->database ?: $this->database;
        $query = new ClientQuery($this, 'D');
        $query->addAnsi($database)->newLine();
        $query->add(0)->newLine();
        $query->add(1)->newLine();
        $query->addUtf($record->encode())->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        return $response->returnCode;
    } // function writeRawRecord

    /**
     * Сохранение записи на сервере.
     *
     * @param MarcRecord $record Запись для сохранения (новая или ранее считанная).
     * @param int $lockFlag Оставить запись заблокированной?
     * @param int $actualize Актуализировать словарь?
     * @param bool $dontParse Не разбирать результат.
     * @return bool|int Новый максимальный MFN в базе данных
     * либо признак сбоя операции.
     */
    public function writeRecord(MarcRecord $record, $lockFlag = 0, $actualize = 1,
                                $dontParse = false)
    {
        if (!$this->_checkConnection())
            return false;

        $database = $record->database ?: $this->database;
        $query = new ClientQuery($this, 'D');
        $query->addAnsi($database)->newLine();
        $query->add($lockFlag)->newLine();
        $query->add($actualize)->newLine();
        $query->addUtf($record->encode())->newLine();
        $response = $this->execute($query);
        if (!$response || !$response->checkReturnCode())
            return false;

        if (!$dontParse) {
            $record->fields = array();
            $temp = $response->readRemainingUtfLines();
            if (count($temp) > 1) {
                $lines = array($temp[0]);
                $lines = array_merge($lines, explode(SHORT_DELIMITER, $temp[1]));
                $record->decode($lines);
                $record->database = $database;
            }
        }

        return $response->returnCode;
    } // function writeRecord

    /**
     * Сохранение нескольких записей на сервере (могут относиться к разным базам).
     *
     * @param array $records Записи.
     * @param int $lockFlag
     * @param int $actualize
     * @param bool $dontParse
     * @return bool Признак успешности операции.
     */
    public function writeRecords(array $records, $lockFlag = 0, $actualize = 1,
                                 $dontParse = false)
    {
        if (!$this->_checkConnection())
            return false;

        if (!$records)
            return true;

        if (count($records) == 1) {
            $this->writeRecord($records[0]);

            return true;
        }

        $query = new ClientQuery($this, '6');
        $query->add($lockFlag)->newLine();
        $query->add($actualize)->newLine();
        foreach ($records as $record) {
            $database = $record->database ?: $this->database;
            $query->addUtf($database . IRBIS_DELIMITER . $record->encode())->newLine();
        }

        $response = $this->execute($query);
        if (!$response)
            return false;

        $response->getReturnCode();

        if (!$dontParse) {
            $lines = $response->readRemainingUtfLines();
            $length = count($records);
            for ($i = 0; $i < $length; $i++) {
                $text = $lines[$i];
                if (is_null_or_empty($text)) {
                    continue;
                }

                $record = $records[$i];
                $record->clear();
                $record->database = $record->database ?: $this->database;
                $recordLines = irbis_to_lines($text);
                $record->parse($recordLines);
            }
        }

        return true;
    } // function writeRecords

    /**
     * Сохранение текстового файла на сервере.
     *
     * @param string $specification Спецификация файла
     * (включая текст файла).
     * @return bool Признак успешности операции.
     */
    public function writeTextFile($specification)
    {
        if (!$this->_checkConnection())
            return false;

        $query = new ClientQuery($this, 'L');
        $query->addAnsi($specification);
        if (!$this->execute($query))
            return false;

        return true;
    } // function writeTextFile

} // class Connection

final class UI
{

    /**
     * @var Connection Активное подключение к серверу.
     */
    public $connection;

    /**
     * Конструктор.
     *
     * @param Connection $connection Активное (!) подключение к серверу.
     * @throws IrbisException
     */
    public function __construct(Connection $connection)
    {
        if (!$connection->isConnected())
            throw new IrbisException();

        $this->connection = $connection;
    }

    /**
     * Вывод выпадающего списка баз данных.
     *
     * @param string $class
     * @param string $selected
     * @throws IrbisException
     */
    public function listDatabases($class = '', $selected = '')
    {
        $dbnnamecat = $this->connection->iniFile->getValue('Main', 'DBNNAMECAT', 'dbnam3.mnu');
        $databases = $this->connection->listDatabases('1..' . $dbnnamecat);
        if (!$databases)
            throw new IrbisException();

        $classText = '';
        if ($class) {
            $classText = "class='{$class}'";
        }
        echo "<select name='catalogBox' $classText>" . PHP_EOL;
        foreach ($databases as $database) {
            $selectedText = '';
            if (same_string($database->name, $selected)) {
                $selectedText = 'selected';
            }
            echo "<option value='{$database->name}' $selectedText>{$database->description}</option>" . PHP_EOL;
        }
        echo "</select>" . PHP_EOL;
    } // function listDatabases

    /**
     * Получение сценариев поиска.
     *
     * @return array
     * @throws IrbisException
     */
    public function getSearchScenario()
    {
        // TODO доделать
        $ini = $this->connection->iniFile;
        $fileName = $ini->getValue("MAIN", 'SearchIni'); // ???
        $section = $ini->findSection("SEARCH");
        if (!$section) {
            throw new IrbisException();
        }
        $result = SearchScenario::parse($ini);

        return $result;
    } // function getSearchScenario

    /**
     * Вывод выпадающего списка сценариев поиска.
     *
     * @param $name
     * @param $scenarios
     * @param string $class
     * @param int $selectedIndex
     * @param string $selectedValue
     */
    public function listSearchScenario($name, $scenarios, $class = '', $selectedIndex = -1,
                                       $selectedValue = '')
    {
        echo "<select name='$name'>" . PHP_EOL;
        $classText = '';
        if ($class) {
            $classText = " class='$class'";
        }
        $index = 0;
        foreach ($scenarios as $scenario) {
            $selectedText = '';
            if ($selectedValue) {
                if (same_string($scenario->prefix, $selectedValue)) {
                    $selectedText = 'selected';
                }
            } else if ($index == $selectedIndex) {
                $selectedText = 'selected';
            }
            echo "<option value='{$scenario->prefix}' $selectedText $classText>{$scenario->name}</option>" . PHP_EOL;
            $index++;
        }
        echo "</select>" . PHP_EOL;
    } // function listSearchScenario

} // class IrbisUI

/**
 * Запись в XRF-файле. Содержит информацию о смещении записи
 * и ее статус.
 */
final class XrfRecord
{
    /**
     * @var int Младшая часть смещения.
     */
    public $low;

    /**
     * @var int Старшая часть смещения.
     */
    public $high;

    /**
     * @var int Статус записи.
     */
    public $status;

    /**
     * Запись (логически или физически) удалена?
     * @return bool
     */
    public function isDeleted()
    {
        return ($this->status & 3) != 0;
    }

    /**
     * Смещение записи.
     * @return int
     */
    public function offset()
    {
        return ($this->high << 32) + $this->low;
    }
} // class XrfRecord

/**
 * XRF-файл.
 */
final class XrfFile
{
    // Файл.
    private $file;

    /**
     * XrfFile constructor.
     * @param $filename
     * @throws IrbisException
     */
    public function __construct($filename)
    {
        $this->file = fopen($filename, 'rb');
        if (!$this->file) {
            throw new IrbisException("Can't open " . $filename);
        }
    } // function __construct

    public function __destruct()
    {
        if ($this->file)
            fclose($this->file);
    } // function __destruct

    /**
     * Считывание записи по MFN.
     * @param int $mfn MFN записи.
     * @return XrfRecord
     */
    public function read($mfn)
    {
        $offset = ($mfn - 1) * 12;
        fseek($this->file, $offset, SEEK_SET);
        $content = fread($this->file, 12);
        $result = new XrfRecord();
        $result->low = unpack("N", $content, 0);
        $result->high = unpack("N", $content, 4);
        $result->status = unpack("N", $content, 8);
        return $result;
    } // function read

} // class XrfFile
