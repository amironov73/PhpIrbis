<?php

//
// Простой клиент для АБИС ИРБИС64.
//

// Кодировки
//
// ВАЖНО! Предполагается, что внутренняя кодировка символов в PHP -- UTF-8
// И строковые литералы в PHP-файлах хранятся также в кодировке UTF-8
// Если это не так, возможны проблемы
//

const UTF_ENCODING = 'UTF-8';
const ANSI_ENCODING = 'Windows-1251';

mb_internal_encoding(UTF_ENCODING);

// Статус записи

const LOGICALLY_DELETED  = 1;  // Запись логически удалена
const PHYSICALLY_DELETED = 2;  // Запись физически удалена
const ABSENT             = 4;  // Запись отсутствует
const NON_ACTUALIZED     = 8;  // Запись не актуализирована
const LAST_VERSION       = 32; // Последняя версия записи
const LOCKED_RECORD      = 64; // Запись заблокирована на ввод

// Распространённые форматы

const ALL_FORMAT       = "&uf('+0')";  // Полные данные по полям
const BRIEF_FORMAT     = '@brief';     // Краткое библиографическое описание
const IBIS_FORMAT      = '@ibiskw_h';  // Формат IBIS (старый)
const INFO_FORMAT      = '@info_w';    // Информационный формат
const OPTIMIZED_FORMAT = '@';          // Оптимизированный формат

// Распространённые поиски

const KEYWORD_PREFIX    = 'K=';  // Ключевые слова
const AUTHOR_PREFIX     = 'A=';  // Индивидуальный автор, редактор, составитель
const COLLECTIVE_PREFIX = 'M=';  // Коллектив или мероприятие
const TITLE_PREFIX      = 'T=';  // Заглавие
const INVENTORY_PREFIX  = 'IN='; // Инвентарный номер, штрих-код или радиометка
const INDEX_PREFIX      = 'I=';  // Шифр документа в базе

// Логические операторы для поиска

const LOGIC_OR                = 0; // Только ИЛИ
const LOGIC_OR_AND            = 1; // ИЛИ и И
const LOGIC_OR_AND_NOT        = 2; // ИЛИ, И, НЕТ (по умолчанию)
const LOGIC_OR_AND_NOT_FIELD  = 3; // ИЛИ, И, НЕТ, И (в поле)
const LOGIC_OR_AND_NOT_PHRASE = 4; // ИЛИ, И, НЕТ, И (в поле), И (фраза)

// Коды АРМ

const ADMINISTRATOR = 'A'; // Адмнистратор
const CATALOGER     = 'C'; // Каталогизатор
const ACQUSITIONS   = 'M'; // Комплектатор
const READER        = 'R'; // Читатель
const CIRCULATION   = 'B'; // Книговыдача
const BOOKLAND      = 'B'; // Книговыдача
const PROVISITON    = 'K'; // Книгообеспеченность

// Команды глобальной корректировки

const ADD_FIELD        = 'ADD';
const DELETE_FIELD     = 'DEL';
const REPLACE_FIELD    = 'REP';
const CHANGE_FIELD     = 'CHA';
const CHANGE_WITH_CASE = 'CHAC';
const DELETE_RECORD    = 'DELR';
const UNDELETE_RECORD  = 'UNDELR';
const CORRECT_RECORD   = 'CORREC';
const CREATE_RECORD    = 'NEWMFN';
const EMPTY_RECORD     = 'EMPTY';
const UNDO_RECORD      = 'UNDOR';
const GBL_END          = 'END';
const GBL_IF           = 'IF';
const GBL_FI           = 'FI';
const GBL_ALL          = 'ALL';
const GBL_REPEAT       = 'REPEAT';
const GBL_UNTIL        = 'UNTIL';
const PUTLOG           = 'PUTLOG';

/**
 * Разделитель строк в ИРБИС.
 */
const IRBIS_DELIMITER = "\x1F\x1E";

/**
 * Короткая версия разделителя строк ИРБИС.
 */
const SHORT_DELIMITER = "\x1E";

/**
 * Пустая ли данная строка?
 *
 * @param string $text Строка для изучения.
 * @return bool
 */
function isNullOrEmpty($text) {
    return (!isset($text) || $text == false || trim($text) == '');
}

/**
 * Строки совпадают с точностью до регистра символов?
 *
 * @param string $str1 Первая строка.
 * @param string $str2 Вторая строка.
 * @return bool
 */
function sameString($str1, $str2) {
    return strcasecmp($str1, $str2) == 0;
}

/**
 * Замена переводов строки с ИРБИСных на обычные.
 *
 * @param string $text Текст для замены.
 * @return mixed
 */
function irbisToDos($text) {
    return str_replace(IRBIS_DELIMITER, "\n", $text);
}

/**
 * Разбивка текста на строки по ИРБИСным разделителям.
 *
 * @param string $text Текст для разбиения.
 * @return array
 */
function irbisToLines($text) {
    return explode(IRBIS_DELIMITER, $text);
}

/**
 * Удаление комментариев из строки.
 *
 * @param string $text Текст для удаления комментариев.
 * @return string
 */
function removeComments($text) {
    if (isNullOrEmpty($text)) {
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
                if ($c == '/') {
                    if ($index + 1 < $length && $text[$index + 1] == '*') {
                        while ($index < $length) {
                            $c = $text[$index];
                            if ($c == "\r" || $c == "\n") {
                                $result .= $c;
                                break;
                            }

                            $index++;
                        }
                    }
                    else {
                        $result .= $c;
                    }
                }
                else if ($c == "'" || $c == '"' || $c == '|') {
                    $state = $c;
                    $result .= $c;
                }
                else {
                    $result .= $c;
                }
                break;
        }

        $index++;
    }

    return $result;
} // function removeComments

/**
 * Подготовка динамического формата
 * для передачи на сервер.
 *
 * В формате должны отсутствовать комментарии
 * и служебные символы (например, перевод
 * строки или табуляция).
 *
 * @param string $text Текст для обработки.
 * @return string
 */
function prepareFormat ($text) {
    $text = removeComments($text);
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
} // function prepareFormat

/**
 * Получение описания по коду ошибки, возвращенному сервером.
 *
 * @param integer $code Код ошибки.
 * @return string Словесное описание ошибки.
 */
function describeError($code) {
    if ($code >= 0) {
        return 'Нет ошибки';
    }

    $errors = array (
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
        -8888 => 'Общая ошибка'
    );

    $result = $errors[$code] ?: 'Неизвестная ошибка';

    return $result;
} // function describeError

/**
 * @return array "Хорошие" коды для readRecord.
 */
function readRecordCodes() {
    return array(-201, -600, -602, -603);
}

/**
 * @return array "Хорошие" коды для readTerms.
 */
function readTermCodes() {
    return array(-202, -203, -204);
}

final class IrbisException extends Exception {
    public function __construct($message = "",
                                $code = 0,
                                Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
} // class IrbisException

/**
 * Подполе записи. Состоит из кода и значения.
 */
final class SubField {
    /**
     * @var string Код подполя.
     */
    public $code;

    /**
     * @var string Значение подполя.
     */
    public $value;

    /**
     * Конструктор подполя.
     *
     * @param string $code Код подполя.
     * @param string $value Значение подполя.
     */
    public function __construct($code='', $value='') {
        $this->code = $code;
        $this->value = $value;
    }

    /**
     * Декодирование подполя из протокольного представления.
     *
     * @param string $line
     */
    public function decode($line) {
        $this->code = $line[0];
        $this->value = substr($line, 1);
    }

    /**
     * Верификация подполя.
     *
     * @param bool $throw Бросать ли исключение при ошибке?
     * @return bool Результат верификации.
     * @throws IrbisException
     */
    public function verify($throw = true) {
        $result = $this->code && $this->value;
        if (!$result && $throw) {
            throw new IrbisException();
        }

        return $result;
    }

    public function __toString() {
        return '^' . $this->code . $this->value;
    }
} // class SubField

/**
 * Поле записи. Состоит из метки и (опционального) значения.
 * Может содержать произвольное количество подполей.
 */
final class RecordField {
    /**
     * @var integer Метка поля.
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
     * Конструктор поля.
     *
     * @param int $tag Метка поля.
     * @param string $value Значение поля.
     */
    public function __construct($tag=0, $value='') {
        $this->tag = $tag;
        $this->value = $value;
    }

    /**
     * Добавление подполя с указанными кодом и значением.
     *
     * @param string $code Код подполя.
     * @param string $value Значение подполя.
     * @return $this
     */
    public function add($code, $value) {
        $subfield = new SubField();
        $subfield->code = $code;
        $subfield->value = $value;
        array_push($this->subfields, $subfield);

        return $this;
    }

    /**
     * Очищает поле (удаляет значение и все подполя).
     *
     * @return $this
     */
    public function clear() {
        $this->value = '';
        $this->subfields = array();

        return $this;
    }

    /**
     * Декодирование поля из протокольного представления.
     *
     * @param string $line
     */
    public function decode($line) {
        $this->tag = intval(strtok($line, "#"));
        $body = strtok('');

        if ($body[0] == '^') {
            $this->value = '';
            $all = explode('^', $body);
        }
        else {
            $this->value = strtok($body, '^');
            $all = explode('^', strtok(''));
        }

        foreach ($all as $one) {
            if (!empty($one)) {
                $sf = new SubField();
                $sf->decode($one);
                array_push($this->subfields, $sf);
            }
        }
    }

    /**
     * Возвращает первое вхождение подполя с указанным кодом.
     *
     * @param string $code Код искомого подполя.
     * @return SubField|null Найденное подполе.
     */
    public function getFirstSubField($code) {
        foreach ($this->subfields as $subfield) {
            if (sameString($subfield->code, $code)) {
                return $subfield;
            }
        }

        return null;
    }

    /**
     * Возвращает значение первого вхождения подполя с указанным кодом.
     *
     * @param string $code Код искомого подполя.
     * @return string
     */
    public function getFirstSubFieldValue($code) {
        foreach ($this->subfields as $subfield) {
            if (sameString($subfield->code, $code)) {
                return $subfield->value;
            }
        }

        return '';
    }

    /**
     * Верификация поля.
     *
     * @param bool $throw Бросать ли исключение при ошибке?
     * @return bool Результат верификации.
     * @throws IrbisException
     */
    public function verify($throw = true) {
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
    }

    public function __toString() {
        $result = $this->tag . '#' . $this->value;

        foreach ($this->subfields as $sf) {
            $result .= $sf;
        }

        return $result;
    }
} // class RecordField

/**
 * Запись. Состоит из произвольного количества полей.
 */
final class MarcRecord {
    /**
     * @var string Имя базы данных, в которой хранится запись.
     */
    public $database = '';

    /**
     * @var integer MFN записи.
     */
    public $mfn = 0;

    /**
     * @var integer Версия записи.
     */
    public $version = 0;

    /**
     * @var integer Статус записи.
     */
    public $status = 0;

    /**
     * @var array Массив полей.
     */
    public $fields = array();

    /**
     * Добавление поля в запись.
     *
     * @param integer $tag Метка поля.
     * @param string $value Значение поля до первого разделителя.
     * @return RecordField Созданное поле.
     */
    public function add($tag, $value='') {
        $field = new RecordField();
        $field->tag = $tag;
        $field->value = $value;
        array_push($this->fields, $field);

        return $field;
    }

    /**
     * Очистка записи (удаление всех полей).
     *
     * @return $this
     */
    public function clear() {
        $this->fields = array();

        return $this;
    }

    /**
     * Декодирование ответа сервера.
     *
     * @param array $lines Массив строк
     * с клиентским представлением записи.
     */
    public function decode(array $lines) {
        // mfn and status of the record
        $firstLine = explode('#', $lines[0]);
        $this->mfn = intval($firstLine[0]);
        $this->status = intval($firstLine[1]);

        // version of the record
        $secondLine = explode('#', $lines[1]);
        $this->version = intval($secondLine[1]);
        $lines = array_slice($lines, 2);

        // fields
        foreach ($lines as $line) {
            if ($line) {
                $field = new RecordField();
                $field->decode($line);
                array_push($this->fields, $field);
            }
        }
    }

    /**
     * Получение значения поля (или подполя)
     * с указанной меткой (и указанным кодом).
     *
     * @param integer $tag Метка поля
     * @param string $code Код подполя
     * @return string|null
     */
    public function fm($tag, $code='') {
        foreach ($this->fields as $field) {
            if ($field->tag == $tag) {
                if ($code) {
                    foreach ($field->subfields as $subfield) {
                        if (strcasecmp($subfield->code, $code) == 0) {
                            return $subfield->value;
                        }
                    }
                } else {
                    return $field->value;
                }
            }
        }

        return null;
    }

    /**
     * Получение массива значений поля (или подполя)
     * с указанной меткой (и указанным кодом).
     *
     * @param integer $tag Искомая метка поля.
     * @param string $code Код подполя.
     * @return array
     */
    public function fma($tag, $code='') {
        $result = array();
        foreach ($this->fields as $field) {
            if ($field->tag == $tag) {
                if ($code) {
                    foreach ($field->subfields as $subfield) {
                        if (strcasecmp($subfield->code, $code) == 0) {
                            if ($subfield->value) {
                                array_push($result, $subfield->value);
                            }
                        }
                    }
                } else {
                    if ($field->value) {
                        array_push($result, $field->value);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Получение указанного поля (с учётом повторения).
     *
     * @param integer $tag Метка поля.
     * @param int $occurrence Номер повторения.
     * @return RecordField|null
     */
    public function getField($tag, $occurrence = 0) {
        foreach ($this->fields as $field) {
            if ($field->tag == $tag) {
                if (!$occurrence) {
                    return $field;
                }

                $occurrence--;
            }
        }

        return null;
    }

    /**
     * Получение массива полей с указанной меткой.
     *
     * @param integer $tag Искомая метка поля.
     * @return array
     */
    public function getFields($tag) {
        $result = array();
        foreach ($this->fields as $field) {
            if ($field->tag == $tag) {
                array_push($result, $field);
            }
        }

        return $result;
    }

    /**
     * @return bool Запись удалена
     * (неважно - логически или физически)?
     */
    public function isDeleted() {
        return boolval($this->status & 3);
    }

    /**
     * Кодирование записи в протокольное представление.
     *
     * @param string $delimiter Разделитель строк.
     * В зависимости от ситуации ИРБИСный или обычный.
     * @return string
     */
    public function encode($delimiter = IRBIS_DELIMITER) {
        $result = $this->mfn . '#' . $this->status . $delimiter
            . '0#' . $this->version . $delimiter;

        foreach ($this->fields as $field) {
            $result .= ($field . $delimiter);
        }

        return $result;
    }

    /**
     * Верификация записи.
     *
     * @param bool $throw Бросать ли исключение при ошибке?
     * @return bool Результат верификации.
     */
    public function verify($throw = true) {
        $result = false;
        foreach ($this->fields as $field) {
            $result = $field->verify($throw);
            if (!$result) {
                break;
            }
        }

        return $result;
    }

    public function __toString() {
        return $this->encode();
    }
} // class MarcRecord

/**
 * Запись в "сыром" ("неразобранном") виде.
 */
final class RawRecord {
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
    public function decode(array $lines) {
        // mfn and status of the record
        $firstLine = explode('#', $lines[0]);
        $this->mfn = intval($firstLine[0]);
        $this->status = intval($firstLine[1]);

        // version of the record
        $secondLine = explode('#', $lines[1]);
        $this->version = intval($secondLine[1]);
        $this->fields = array_slice($lines, 2);
    }

    /**
     * Кодирование записи в протокольное представление.
     *
     * @param string $delimiter Разделитель строк.
     * В зависимости от ситуации ИРБИСный или обычный.
     * @return string
     */
    public function encode($delimiter=IRBIS_DELIMITER) {
        $result = $this->mfn . '#' . $this->status . $delimiter
            . '0#' . $this->version . $delimiter;

        foreach ($this->fields as $field) {
            $result .= ($field . $delimiter);
        }

        return $result;
    }
} // class RawRecord

/**
 * Строка найденной записи в ответе сервера.
 */
final class FoundLine {
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
    public static function parse(array $lines) {
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            $item = new FoundLine();
            $item->mfn = intval($parts[0]);
            $item->description = $parts[1];
            array_push($result, $item);
        }

        return $result;
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа сервера.
     * @return array Массив MFN найденных записей.
     */
    public static function parseMfn(array $lines) {
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            $mfn = intval($parts[0]);
            array_push($result, $mfn);
        }

        return $result;
    }

    /**
     * Преобразование в массив библиографических описаний.
     *
     * @param array $found Найденные записи.
     * @return array Массив описаний.
     */
    public static function toDescription(array $found) {
        $result = array();
        foreach ($found as $item) {
            array_push($result, $item->description);
        }

        return $result;
    }

    /**
     * Преобразование в массив MFN.
     *
     * @param array $found Найденные записи.
     * @return array Массив MFN.
     */
    public static function toMfn(array $found) {
        $result = array();
        foreach ($found as $item) {
            array_push($result, $item->mfn);
        }

        return $result;
    }

    public function __toString() {
        return $this->description
            ? $this->mfn . '#' . $this->description
            : strval($this->mfn);
    }
} // class FoundLine

/**
 * Пара строк в меню.
 */
final class MenuEntry {
    public $code, $comment;

    public function __toString() {
        return $this->code . ' - ' . $this->comment;
    }
} // class MenuEntry

/**
 * Файл меню. Состоит из пар строк (см. MenuEntry).
 */
final class MenuFile {
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
    public function add($code, $comment) {
        $entry = new MenuEntry();
        $entry->code = $code;
        $entry->comment = $comment;
        array_push($this->entries, $entry);

        return $this;
    }

    /**
     * Отыскивает запись, соответствующую данному коду.
     *
     * @param string $code
     * @return mixed|null
     */
    public function getEntry($code) {
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
    public function getValue($code, $defaultValue='') {
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
    public function parse(array $lines) {
        $length = count($lines);
        for ($i=0; $i < $length; $i += 2) {
            $code = $lines[$i];
            if (!$code || substr($code, 5) == '*****') {
                break;
            }

            $comment = $lines[$i + 1];
            $entry = new MenuEntry();
            $entry->code = $code;
            $entry->comment = $comment;
            array_push($this->entries, $entry);
        }
    }

    /**
     * Отрезание лишних символов в коде.
     *
     * @param string $code Код.
     * @return string Очищенный код.
     */
    public static function trimCode($code) {
        $result = trim($code, '-=:');

        return $result;
    }

    public function __toString() {
        $result = '';

        foreach ($this->entries as $entry) {
            $result .= ($entry . PHP_EOL);
        }
        $result .= "*****\n";

        return $result;
    }
} // class MenuFile

/**
 * Строка INI-файла. Состоит из ключа
 * и (опционального) значения.
 */
final class IniLine {
    /**
     * @var string Ключ.
     */
    public $key;

    /**
     * @var string Значение.
     */
    public $value;

    public function __toString() {
        return $this->key . ' = ' . $this->value;
    }
} // class IniLine

/**
 * Секция INI-файла. Состоит из строк
 * (см. IniLine).
 */
final class IniSection {
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
    public function find($key) {
        foreach ($this->lines as $line) {
            if (strcasecmp($line->key, $key) == 0) {
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
    public function getValue($key, $defaultValue = '') {
        $found = $this->find($key);
        return $found ? $found->value : $defaultValue;
    }

    /**
     * Удаление элемента с указанным ключом.
     *
     * @param string $key Имя ключа.
     * @return IniSection
     */
    public function remove($key) {
        for ($i=0; $i < count($this->lines); $i++) {
            if (sameString($this->lines[$i]->key, $key)) {
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
    public function setValue($key, $value) {
        if (!$value) {
            remove($key);
        } else {
            $item = $this->find($key);
            if ($item) {
                $item->value = $value;
            } else {
                $item = new IniLine();
                $item->key = $key;
                $item->value = $value;
                array_push($this->lines, $item);
            }
        }
    }

    public function __toString() {
        $result = '[' . $this->name . ']' . PHP_EOL;

        foreach ($this->lines as $line) {
            $result .= ($line . PHP_EOL);
        }

        return $result;
    }
} // class IniSection

/**
 * INI-файл. Состоит из секций (см. IniSection).
 */
final class IniFile {
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
    public function findSection($name) {
        foreach ($this->sections as $section) {
            if (strcasecmp($section->name, $name) == 0) {
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
    public function getOrCreateSection($name) {
        $result = $this->findSection($name);
        if (!$result) {
            $result = new IniSection();
            $result->name = $name;
            array_push($this->sections, $result);
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
    public function getValue($sectionName, $key, $defaultValue = '') {
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
    public function parse(array $lines) {
        $section = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (isNullOrEmpty($trimmed)) {
                continue;
            }

            if ($trimmed[0] == '[') {
                $name = substr($trimmed, 1, strlen($trimmed) - 2);
                $section = $this->getOrCreateSection($name);
            } else if ($section) {
                $parts = explode('=', $trimmed, 2);
                $key = $parts[0];
                $value = $parts[1];
                $item = new IniLine();
                $item->key = $key;
                $item->value = $value;
                array_push($section->lines, $item);
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
    public function setValue($sectionName, $key, $value) {
        $section = $this->getOrCreateSection($sectionName);
        $section->setValue($key, $value);

        return $this;
    }

    public function __toString() {
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
 * Узел дерева TRE-файла.
 */
final class TreeNode {
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
    public function __construct($value = '') {
        $this->value = $value;
    }

    /**
     * Добавление дочернего узла с указанным значением.
     *
     * @param $value
     * @return $this
     */
    public function add($value) {
        $child = new TreeNode();
        $child->value = $value;
        array_push($this->children, $child);

        return $this;
    }

    public function __toString() {
        return $this->value;
    }
} // class TreeNode

/**
 * Дерево, хранящееся в TRE-файле.
 */
final class TreeFile {
    /**
     * @var array Корни дерева.
     */
    public $roots = array();

    private static function arrange1(array $list, $level) {
        $count = count($list);
        $index = 0;

        while ($index < $count) {
            $next = self::arrange2($list, $level, $index, $count);
            $index = $next;
        }
    }

    private static function arrange2(array $list, $level, $index, $count) {
        $next = $index + 1;
        $level2 = $level + 1;

        $parent = $list[$index];
        while ($next < $count) {
            $child = $list[$next];
            if ($child->level < $level) {
                break;
            }

            if ($child->level == $level2) {
                array_push($parent->children, $child);
            }

            $next++;
        }

        return $next;
    }

    private static function countIndent($text) {
        $result = 0;
        $length = strlen($text);
        for($i = 0; $i < $length; $i++) {
           if ($text[$i] == "\t") {
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
    public function addRoot($value) {
        $result = new TreeNode();
        $result->value = $value;
        array_push($this->roots, $result);

        return $result;
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки с ответом сервера.
     * @throws IrbisException
     */
    public function parse(array $lines) {
        if (!count($lines)) {
            return;
        }

        $list = array();
        $currentLevel = 0;
        $line = $lines[0];
        if (self::countIndent($line) != 0) {
            throw new IrbisException();
        }

        array_push($list, new TreeNode($line));
        $lines = array_slice($lines, 1);
        foreach ($lines as $line) {
            if (isNullOrEmpty($line)) {
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
            array_push($list, $node);
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
                array_push($this->roots, $item);
            }
        }
    }
} // class TreeFile

/**
 * Информация о базе данных ИРБИС.
 */
final class DatabaseInfo {
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

    static function parseLine($line) {
        $result = array();
        $items = explode(SHORT_DELIMITER, $line);
        foreach ($items as $item) {
            array_push($result, intval($item));
        }

        return $result;
    }

    /**
     * Разбор ответа сервера (см. getDatabaseInfo).
     *
     * @param array $lines Ответ сервера.
     * @return DatabaseInfo
     */
    public static function parseResponse(array $lines) {
        $result = new DatabaseInfo();
        $result->logicallyDeletedRecords = self::parseLine($lines[0]);
        $result->physicallyDeletedRecords = self::parseLine($lines[1]);
        $result->nonActualizedRecords = self::parseLine($lines[2]);
        $result->lockedRecords = self::parseLine($lines[3]);
        $result->maxMfn = intval($lines[4]);
        $result->databaseLocked = intval($lines[5]) != 0;

        return $result;
    }

    /**
     * Получение списка баз данных из MNU-файла.
     *
     * @param MenuFile $menu Меню.
     * @return array
     */
    public static function parseMenu(MenuFile $menu) {
        $result = array();
        foreach ($menu->entries as $entry) {
            $name = $entry->code;
            if ($name == '*****') {
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
            array_push($result, $db);
        }

        return $result;
    }

    public function __toString() {
        return $this->name;
    }
} // class DatabaseInfo

/**
 * Информация о запущенном на ИРБИС-сервере процессе.
 */
final class ProcessInfo {
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

    public static function parse(array $lines) {
        $result = array();
        $processCount = intval($lines[0]);
        $linesPerProcess = intval($lines[1]);
        if (!$processCount || !$linesPerProcess) {
            return $result;
        }

        $lines = array_slice($lines, 2);
        for($i = 0; $i < $processCount; $i++) {
            $process = new ProcessInfo();
            $process->number        = $lines[0];
            $process->ipAddress     = $lines[1];
            $process->name          = $lines[2];
            $process->clientId      = $lines[3];
            $process->workstation   = $lines[4];
            $process->started       = $lines[5];
            $process->lastCommand   = $lines[6];
            $process->commandNumber = $lines[7];
            $process->processId     = $lines[8];
            $process->state         = $lines[9];

            array_push($result, $process);
            $lines = array_slice($lines, $linesPerProcess);
        }

        return $result;
    }

    public function __toString() {
        return "{$this->number} {$this->ipAddress} {$this->name}";
    }
} // class ProcessInfo

/**
 * Информация о версии ИРБИС-сервера.
 */
final class VersionInfo {
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
    public function parse(array $lines) {
        if (count($lines) == 3) {
            $this->version = $lines[0];
            $this->connectedClients = intval($lines[1]);
            $this->maxClients = intval($lines[2]);
        } else {
            $this->organization = $lines[0];
            $this->version = $lines[1];
            $this->connectedClients = intval($lines[2]);
            $this->maxClients = intval($lines[3]);
        }
    }

    public function __toString() {
        return $this->version;
    }
} // class VersionInfo

/**
 * Информация о клиенте, подключенном к серверу ИРБИС
 * (не обязательно о текущем).
 */
final class ClientInfo {
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
    public function parse(array $lines) {
        $this->number        = $lines[0];
        $this->ipAddress     = $lines[1];
        $this->port          = $lines[2];
        $this->name          = $lines[3];
        $this->id            = $lines[4];
        $this->workstation   = $lines[5];
        $this->registered    = $lines[6];
        $this->acknowledged  = $lines[7];
        $this->lastCommand   = $lines[8];
        $this->commandNumber = $lines[9];
    }

    public function __toString() {
        return $this->ipAddress;
    }
} // class ClientInfo

/**
 * Информация о зарегистрированном пользователе системы
 * (по данным client_m.mnu).
 */
final class UserInfo {
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

    public static function formatPair($prefix, $value, $default) {
        if (sameString($value, $default)) {
            return '';
        }

        return $prefix . '=' . $value . ';';
    }

    /**
     * Формирование строкового представления пользователя.
     *
     * @return string
     */
    public function encode() {
        return $this->name . "\r\n"
            . $this->password . "\r\n"
            . self::formatPair('C', $this->cataloger,     'irbisc.ini')
            . self::formatPair('R', $this->reader,        'irbisr.ini')
            . self::formatPair('B', $this->circulation,   'irbisb.ini')
            . self::formatPair('M', $this->acquisitions,  'irbism.ini')
            . self::formatPair('K', $this->provision,     'irbisk.ini')
            . self::formatPair('A', $this->administrator, 'irbisa.ini');
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки ответа сервера.
     * @return array
     */
    public static function parse(array $lines) {
        $result = array();
        $userCount = intval($lines[0]);
        $linesPerUser = intval($lines[1]);
        if (!$userCount || !$linesPerUser) {
            return $result;
        }

        $lines = array_slice($lines, 2);
        for($i = 0; $i < $userCount; $i++) {
            if (!$lines) {
                break;
            }

            $user = new UserInfo();
            $user->number        = $lines[0];
            $user->name          = $lines[1];
            $user->password      = $lines[2];
            $user->cataloger     = $lines[3];
            $user->reader        = $lines[4];
            $user->circulation   = $lines[5];
            $user->acquisitions  = $lines[6];
            $user->provision     = $lines[7];
            $user->administrator = $lines[8];
            array_push($result, $user);

            $lines = array_slice($lines, $linesPerUser + 1);
        }

        return $result;
    }

    public function __toString() {
        return $this->name;
    }
} // class UserInfo

/**
 * Данные для метода printTable.
 */
final class TableDefinition {
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

    public function __toString() {
        return $this->table;
    }
} // class TableDefinition

/**
 * Статистика работы ИРБИС-сервера.
 */
final class ServerStat {
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
    public function parse(array $lines) {
        $this->totalCommandCount = intval($lines[0]);
        $this->clientCount = intval($lines[1]);
        $linesPerClient = intval($lines[2]);
        if (!$linesPerClient) {
            return;
        }

        $lines = array_slice($lines, 3);

        for($i=0; $i < $this->clientCount; $i++) {
            $client = new ClientInfo();
            $client->parse($lines);
            array_push($this->runningClients, $client);
            $lines = array_slice($lines, $linesPerClient + 1);
        }
    }

    public function __toString() {
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
final class PostingParameters {
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
final class TermParameters {
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
final class TermInfo {
    /**
     * @var int Количество ссылок.
     */
    public $count = 0;

    /**
     * @var string Поисковый термин.
     */
    public $text = '';

    public static function parse(array $lines) {
        $result = array();
        foreach ($lines as $line) {
            if (!isNullOrEmpty($line)) {
                $parts = explode('#', $line, 2);
                $term = new TermInfo();
                $term->count = intval($parts[0]);
                $term->text = $parts[1];
                array_push($result, $term);
            }
        }

        return $result;
    }

    public function __toString() {
        return $this->count . '#' . $this->text;
    }
} // class TermInfo

/**
 * Постинг термина в поисковом индексе.
 */
final class TermPosting {
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
    public static function parse(array $lines) {
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 5);
            if (count($parts) < 4) {
                break;
            }

            $item = new TermPosting();
            $item->mfn        = intval($parts[0]);
            $item->tag        = intval($parts[1]);
            $item->occurrence = intval($parts[2]);
            $item->count      = intval($parts[3]);
            $item->text       = $parts[4];
            array_push($result, $item);
        }

        return $result;
    }

    public function __toString() {
        return $this->mfn . '#' . $this->tag . '#'
            . $this->occurrence . '#' . $this->count
            . '#' . $this->text;
    }
} // class TermPosting

/**
 * Параметры для поиска записей (метод searchEx).
 */
final class SearchParameters {
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
final class SearchScenario {
    /**
     * @var string Название поискового атрибута
     * (автор, инвентарный номер).
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

    static function get(IniSection $section, $name, $index) {
        $fullName = 'Item' . $name . $index;
        return $section->getValue($fullName);
    }

    /**
     * Разбор INI-файла.
     *
     * @param IniFile $iniFile
     * @return array
     */
    public static function parse(IniFile $iniFile) {
        $result = array();
        $section = $iniFile->findSection('SEARCH');
        if ($section) {
            $count = intval($section->getValue('ItemNumb'));
            for($i=0; $i < $count; $i++) {
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
                array_push($result, $scenario);
            }
        }

        return $result;
    }
} // class SearchScenario

/**
 * PAR-файл -- содержит пути к файлам базы данных ИРБИС.
 */
final class ParFile {

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
    public function __construct($mst = '') {
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
    public function parse(array $lines) {
        $map = array();
        foreach ($lines as $line) {
            if (isNullOrEmpty($line)) {
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
    }

    public function __toString() {
        return '1='  . $this->xrf . PHP_EOL
            .  '2='  . $this->mst . PHP_EOL
            .  '3='  . $this->cnt . PHP_EOL
            .  '4='  . $this->n01 . PHP_EOL
            .  '5='  . $this->n02 . PHP_EOL
            .  '6='  . $this->l01 . PHP_EOL
            .  '7='  . $this->l02 . PHP_EOL
            .  '8='  . $this->ifp . PHP_EOL
            .  '9='  . $this->any . PHP_EOL
            .  '10=' . $this->pft . PHP_EOL
            .  '11=' . $this->ext . PHP_EOL;
    }

} // class ParFile

/**
 * Строка OPT-файла.
 */
final class OptLine {
    /**
     * @var string Паттерн.
     */
    public $pattern = '';

    /**
     * @var string Соответствующий рабочий лист.
     */
    public $worksheet = '';

    public function parse($text) {
        $parts = preg_split("/\s+/", trim($text), 2, PREG_SPLIT_NO_EMPTY);
        if (count($parts) != 2) {
            throw new IrbisException();
        }

        $this->pattern = $parts[0];
        $this->worksheet = $parts[1];
    }

    public function __toString() {
        return $this->pattern . ' ' . $this->worksheet;
    }
} // class OptLine

/**
 * OPT-файл -- файл оптимизации рабочих листов и форматов показа.
 */
final class OptFile {
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
    public function getWorksheet(MarcRecord $record) {
        return $record.fm($this->worksheetTag);
    }

    /**
     * Разбор ответа сервера.
     *
     * @param array $lines Строки OPT-файла.
     * @throws IrbisException
     */
    public function parse(array $lines) {
        $this->worksheetTag = intval($lines[0]);
        $this->worksheetLength = intval($lines[1]);
        $lines = array_slice($lines, 2);
        foreach ($lines as $line) {
            if (isNullOrEmpty($line)) {
                continue;
            }

            if ($line[0] == '*') {
                break;
            }

            $item = new OptLine();
            $item->parse($line);
            array_push($this->lines, $item);
        }
    }

    public static function sameChar($pattern, $testable) {
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
    public static function sameText($pattern, $testable) {
        if (!$pattern) {
            return false;
        }

        if (!$testable) {
            return $pattern[0] == '+';
        }

        $patternIndex = 0;
        $testableIndex = 0;
        while (true) {
            $patternChar = $pattern[$patternIndex];
            $testableChar = $testable[$testableIndex];
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
    }

    /**
     * Подбор значения для указанного текста.
     *
     * @param string $text Проверяемый текст.
     * @return string|null Найденное значение либо null.
     */
    public function resolveWorksheet($text) {
        foreach ($this->lines as $line) {
            if (self::sameText($line->pattern, $text)) {
                return $line->worksheet;
            }
        }

        return null;
    }

    public function __toString() {
        $result = strval($this->worksheetTag) . PHP_EOL
            . strval($this->worksheetLength) . PHP_EOL;

        foreach ($this->lines as $line) {
            $result .= (strval($line) . PHP_EOL);
        }

        $result .= '*****' . PHP_EOL;

        return $result;
    }

} // class OptFile

/**
 * Оператор глобальной корректировки с параметрами.
 */
final class GblStatement {
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
                                $format1    = 'XXXXXXXXX',
                                $format2    = 'XXXXXXXXX')
    {
        $this->command = $command;
        $this->parameter1 = $parameter1;
        $this->parameter2 = $parameter2;
        $this->format1 = $format1;
        $this->format2 = $format2;
    }

    public function __toString() {
        return $this->command . IRBIS_DELIMITER
            . $this->parameter1 . IRBIS_DELIMITER
            . $this->parameter2 . IRBIS_DELIMITER
            . $this->format1 . IRBIS_DELIMITER
            . $this->format2 . IRBIS_DELIMITER;
    }

} // class GblStatement

/**
 * Установки для глобальной корректировки.
 */
final class GblSettings {
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
     * @var int MFN первой записи.
     */
    public $firstRecord = 0;

    /**
     * @var bool Применять формальный контроль?
     */
    public $formalControl = false;

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
     * @var int Число обрабатываемых записей.
     */
    public $numberOfRecords = 0;

    /**
     * @var string Поисковое выражение.
     */
    public $searchExpression = '';

    /**
     * @var array Массив операторов.
     */
    public $statements = array();

} // class GblSettings

/**
 * Клиентский запрос.
 */
final class ClientQuery {
    private $accumulator = '';

    public function __construct(IrbisConnection $connection, $command) {
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
     * @param integer $value Число.
     * @return $this
     */
    public function add($value) {
        $this->addAnsi(strval($value));

        return $this;
    }

    /**
     * Добавляем текст в кодировке ANSI.
     *
     * @param string $value Добавляемый текст.
     * @return $this
     */
    public function addAnsi($value) {
        $converted = mb_convert_encoding($value, ANSI_ENCODING, UTF_ENCODING);
        $this->accumulator .= $converted;

        return $this;
    }

    /**
     * Добавляем текст в кодировке UTF-8.
     *
     * @param string $value Добавляемый текст.
     * @return $this
     */
    public function addUtf($value) {
        $this->accumulator .= $value;

        return $this;
    }

    /**
     * Добавляем перевод строки.
     *
     * @return $this
     */
    public function newLine() {
        $this->accumulator .= chr(10);

        return $this;
    }

    public function __toString() {
        return strlen($this->accumulator) . chr(10) . $this->accumulator;
    }
} // class ClientQuery

/**
 * Ответ сервера.
 */
final class ServerResponse {
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

    private $answer;
    private $offset;
    private $answerLength;

    public function __construct($socket) {
        $this->answer = '';
        while ($buf = socket_read($socket, 2048)) {
            $this->answer .= $buf;
        }
        $this->offset = 0;
        $this->answerLength = strlen($this->answer);

        $this->command = $this->readAnsi();
        $this->clientId = $this->readInteger();
        $this->queryId = $this->readInteger();
        $this->answerSize = $this->readInteger();
        $this->serverVersion = $this->readAnsi();
        for ($i=0; $i < 5; $i++) {
            $this->readAnsi();
        }
    }

    /**
     * Проверка кода возврата.
     *
     * @param array $goodCodes Разрешенные коды возврата.
     * @throws IrbisException
     */
    public function checkReturnCode(array $goodCodes=array()) {
        if ($this->getReturnCode() < 0) {
            if (!in_array($this->returnCode, $goodCodes)) {
                throw new IrbisException(describeError($this->returnCode),
                    $this->returnCode);
            }
        }
    }

    /**
     * Отладочная печать.
     */
    public function debug() {
        file_put_contents('php://stderr', print_r($this->answer, TRUE));
    }

    /**
     * Чтение строки без преобразования кодировок.
     *
     * @return string Прочитанная строка.
     */
    public function getLine() {
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
    public function getReturnCode() {
        $this->returnCode = $this->readInteger();
        return $this->returnCode;
    }

    /**
     * Чтение строки в кодировке ANSI.
     *
     * @return string Прочитанная строка.
     */
    public function readAnsi() {
        $result = $this->getLine();
        $result = mb_convert_encoding($result, UTF_ENCODING, ANSI_ENCODING);

        return $result;
    }

    /**
     * Чтение целого числа.
     *
     * @return int Прочитанное число.
     */
    public function readInteger() {
        $line = $this->getLine();

        return intval($line);
    }

    /**
     * Чтение оставшихся строк в кодировке ANSI.
     *
     * @return array
     */
    public function readRemainingAnsiLines() {
        $result = array();

        while($this->offset < $this->answerLength) {
            $line = $this->readAnsi();
            array_push($result, $line);
        }

        return $result;
    }

    /**
     * Чтение оставшегося текста в кодировке ANSI.
     *
     * @return bool|string
     */
    public function readRemainingAnsiText() {
        $result = substr($this->answer, $this->offset);
        $this->offset = $this->answerLength;
        $result = mb_convert_encoding($result, mb_internal_encoding(), ANSI_ENCODING);

        return $result;
    }

    /**
     * Чтение оставшихся строк в кодировке UTF-8.
     *
     * @return array
     */
    public function readRemainingUtfLines() {
        $result = array();

        while($this->offset < $this->answerLength) {
            $line = $this->readUtf();
            array_push($result, $line);
        }

        return $result;
    }

    /**
     * Чтение оставшегося текста в кодировке UTF-8.
     *
     * @return bool|string
     */
    public function readRemainingUtfText() {
        $result = substr($this->answer, $this->offset);
        $this->offset = $this->answerLength;

        return $result;
    }

    /**
     * Чтение строки в кодировке UTF-8.
     *
     * @return string
     */
    public function readUtf() {
        return $this->getLine();
    }
} // class ServerResponse

/**
 * Подключение к ИРБИС-серверу.
 */
final class IrbisConnection {
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
    private $debug = false;

    //================================================================

    function __destruct() {
        $this->disconnect();
    }

    //================================================================

    /**
     * Актуализация всех неактуализированных записей
     * в указанной базе данных.
     *
     * @param string $database Имя базы данных.
     * @return bool Признак успешности операции.
     * @throws IrbisException
     */
    public function actualizeDatabase($database) {
        return $this->actualizeRecord($database, 0);
    }

    /**
     * Актуализация записи с указанным MFN.
     *
     * @param string $database Имя базы данных.
     * @param integer $mfn MFN, подлежащий актуализации.
     * @return bool Признак успещности операции.
     * @throws IrbisException
     */
    public function actualizeRecord($database, $mfn) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'F');
        $query->addAnsi($database)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();

        return true;
    }

    /**
     * Подключение к серверу ИРБИС64.
     *
     * @return bool
     * @throws IrbisException
     */
    function connect() {
        if ($this->connected) {
            return true;
        }

    AGAIN:
        $this->clientId = rand(100000, 900000);
        $this->queryId = 1;
        $query = new ClientQuery($this, 'A');
        $query->addAnsi($this->username)->newLine();
        $query->addAnsi($this->password);

        if (!$response = $this->execute($query)) {
            return false;
        }

        $response->getReturnCode();
        if ($response->returnCode == -3337) {
            goto AGAIN;
        }

        if ($response->returnCode < 0) {
            return false;
        }

        $this->connected = true;
        $this->serverVersion = $response->serverVersion;
        $this->interval = intval($response->readUtf());
        $lines = $response->readRemainingAnsiLines();
        $this->iniFile = new IniFile();
        $this->iniFile->parse($lines);

        return true;
    }

    /**
     * Создание базы данных.
     *
     * @param string $database Имя создаваемой базы.
     * @param string $description Описание в свободной форме.
     * @param int $readerAccess Читатель будет иметь доступ?
     * @return bool
     * @throws IrbisException
     */
    function createDatabase($database, $description, $readerAccess=1) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'T');
        $query->addAnsi($database)->newLine();
        $query->addAnsi($description)->newLine();
        $query->add($readerAccess)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();

        return true;
    }

    /**
     * Создание словаря в указанной базе данных.
     *
     * @param string $database Имя базы данных.
     * @return bool
     * @throws IrbisException
     */
    public function createDictionary($database) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'Z');
        $query->addAnsi($database)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();

        return true;
    }

    /**
     * Удаление указанной базы данных.
     *
     * @param string $database Имя удаляемой базы данных.
     * @return bool
     * @throws IrbisException
     */
    public function deleteDatabase($database) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'W');
        $query->addAnsi($database)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();

        return true;
    }

    /**
     * Удаление на сервере указанного файла.
     *
     * @param string $fileName Спецификация файла.
     * @throws IrbisException
     */
    public function deleteFile($fileName) {
        $this->formatRecord("&uf('+9K$fileName')", 1);
    }

    /**
     * Удаление записи по её MFN.
     *
     * @param integer $mfn MFN удаляемой записи.
     * @throws IrbisException
     */
    public function deleteRecord($mfn) {
        $record = $this->readRecord($mfn);
        // TODO правильно реагировать, если запись не удалось прочитать
        if (!$record->isDeleted()) {
            $record->status |= LOGICALLY_DELETED;
            $this->writeRecord($record);
        }
    }

    /**
     * Отключение от сервера.
     *
     * @return bool
     */
    public function disconnect() {
        if (!$this->connected) {
            return true;
        }

        $query = new ClientQuery($this, 'B');
        $query->addAnsi($this->username);
        $this->execute($query);
        $this->connected = false;

        return true;
    }

    /**
     * Отправка клиентского запроса на сервер
     * и получение ответа от него.
     *
     * @param ClientQuery $query Клиентский запрос.
     * @return bool|ServerResponse Ответ сервера.
     */
    public function execute(ClientQuery $query) {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return false;
        }

        if (!socket_connect($socket, $this->host, $this->port)) {
            socket_close($socket);
            return false;
        }

        $packet = strval($query);

        if ($this->debug) {
            file_put_contents('php://stderr', print_r($packet, TRUE));
        }

        socket_write($socket, $packet, strlen($packet));
        $response = new ServerResponse($socket);
        if ($this->debug) {
            $response->debug();
        }
        $this->queryId++;

        return $response;
    }

    /**
     * Выполнение произвольной команды.
     *
     * @param string $command Код команды.
     * @param array $params Опциональные параметры в кодировке ANSI.
     * @return bool|ServerResponse
     */
    public function executeAnyCommand($command, array $params=[]) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, $command);
        foreach ($params as $param) {
            $query->addAnsi($param)->newLine();
        }

        $response = $this->execute($query);

        return $response;
    }

    /**
     * Форматирование записи с указанным MFN.
     *
     * @param string $format Текст формата.
     * @param integer $mfn MFN записи.
     * @return bool|string
     * @throws IrbisException
     */
    public function formatRecord($format, $mfn) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'G');
        $query->addAnsi($this->database)->newLine();
        $prepared = prepareFormat($format);
        $query->addAnsi($prepared)->newLine();
        $query->add(1)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = $response->readRemainingUtfText();

        return $result;
    }

    /**
     * Форматирование записи в клиентском представлении.
     *
     * @param string $format Текст формата.
     * @param MarcRecord $record Запись.
     * @return bool|string
     * @throws IrbisException
     */
    public function formatVirtualRecord($format, MarcRecord $record) {
        if (!$this->connected) {
            return false;
        }

        if (!$record) {
            return false;
        }

        $query = new ClientQuery($this, 'G');
        $database = $record->database ?: $this->database;
        $query->addAnsi($this->database)->newLine();
        $prepared = prepareFormat($format);
        $query->addAnsi($prepared)->newLine();
        $query->add(-2)->newLine();
        $query->addUtf($record->encode());
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = $response->readRemainingUtfText();

        return $result;
    }

    /**
     * Форматирование записи с указанным MFN. 
     * Текст формата может содержать любые символы Unicode.
     *
     * @param string $format Текст формата.
     * @param integer $mfn MFN записи.
     * @return bool|string
     * @throws IrbisException
     */
    public function formatRecordUtf($format, $mfn) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'G');
        $query->addAnsi($this->database)->newLine();
        $prepared = prepareFormat($format);
        $query->addUtf('!' . $prepared)->newLine();
        $query->add(1)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = $response->readRemainingUtfText();

        return $result;
    }

    /**
     * Расформатирование нескольких записей.
     *
     * @param string $format Формат.
     * @param array $mfnList Массив MFN.
     * @return array|bool
     * @throws IrbisException
     */
    public function formatRecords($format, array $mfnList) {
        if (!$this->connected) {
            return false;
        }

        if (!$mfnList) {
            return array();
        }

        $query = new ClientQuery($this, 'G');
        $query->addAnsi($this->database)->newLine();
        $prepared = prepareFormat($format);
        $query->addAnsi($prepared)->newLine();
        $query->add(count($mfnList))->newLine();
        foreach ($mfnList as $mfn) {
            $query->add($mfn)->newLine();
        }
        $response = $this->execute($query);
        $response->checkReturnCode();
        $lines = $response->readRemainingUtfLines();
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            array_push($result, irbisToDos($parts[1]));
        }

        return $result;
    }

    /**
     * Получение информации о базе данных.
     *
     * @param string $database Имя базы данных.
     * @return bool|DatabaseInfo
     * @throws IrbisException
     */
    public function getDatabaseInfo($database = '') {
        if (!$this->connected) {
            return false;
        }

        $database = $database ?: $this->database;
        $query = new ClientQuery($this, '0');
        $query->addAnsi($database);
        $response = $this->execute($query);
        $response->checkReturnCode();
        $lines = $response->readRemainingAnsiLines();
        $result = DatabaseInfo::parseResponse($lines);

        return $result;
    }

    /**
     * Получение максимального MFN для указанной базы данных.
     *
     * @param string $database Имя базы данных.
     * @return bool|integer
     * @throws IrbisException
     */
    public function getMaxMfn($database) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'O');
        $query->addAnsi($database);
        $response = $this->execute($query);
        $response->checkReturnCode();

        return $response->returnCode;
    }

    /**
     * Получение статистики с сервера.
     *
     * @return bool|ServerStat
     * @throws IrbisException
     */
    public function getServerStat() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '+1');
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = new ServerStat();
        $result->parse($response->readRemainingAnsiLines());

        return $result;
    }

    /**
     * Получение версии сервера.
     *
     * @return bool|VersionInfo
     * @throws IrbisException
     */
    public function getServerVersion() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '1');
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = new VersionInfo();
        $result->parse($response->readRemainingAnsiLines());

        return $result;
    }

    /**
     * Получение списка пользователей с сервера.
     *
     * @return array|bool
     * @throws IrbisException
     */
    public function getUserList() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '+9');
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = UserInfo::parse($response->readRemainingAnsiLines());

        return $result;
    }

    /**
     * Глобальная корректировка.
     *
     * @param GblSettings $settings Параметры корректировки.
     * @return array|bool
     * @throws IrbisException
     */
    public function globalCorrection(GblSettings $settings) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '5');
        $database = $settings->database ?: $this->database;
        $query->addAnsi($database)->newLine();
        $query->add(intval($settings->actualize))->newLine();
        if  (!isNullOrEmpty($settings->filename)) {
            $query->addAnsi('@' + $settings->filename)->newLine();
        } else {
            $encoded = '!0' . IRBIS_DELIMITER;
            foreach ($settings->statements as $statement) {
                $encoded .= strval($statement);
            }
            $encoded .= IRBIS_DELIMITER;
            $query->addUtf($encoded)->newLine();
        }
        $query->addAnsi($settings->searchExpression)->newLine();
        $query->add($settings->firstRecord)->newLine();
        $query->add($settings->numberOfRecords)->newLine();
        $query->newLine();
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

        if (!$settings->formalControl) {
            $query->addAnsi('*')->newLine();
        }

        if (!$settings->autoin) {
            $query->addAnsi('&')->newLine();
        }

        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = $response->readRemainingAnsiLines();

        return $result;
    }

    /**
     * @return bool Получение статуса,
     * подключен ли клиент в настоящее время.
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * Получение списка баз данных с сервера.
     *
     * @param string $specification Спецификация файла со списком баз.
     * @return array|bool
     */
    public function listDatabases($specification = '1..dbnam2.mnu') {
        if (!$this->connected) {
            return false;
        }

        $menu = $this->readMenuFile($specification);
        $result = DatabaseInfo::parseMenu($menu);

        return $result;
    }

    /**
     * Получение списка файлов.
     *
     * @param string $specification Спецификация.
     * @return array|bool
     */
    public function listFiles($specification) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '!');
        $query->addAnsi($specification)->newLine();
        $response = $this->execute($query);

        $lines = $response->readRemainingAnsiLines();
        $result = array();
        foreach ($lines as $line) {
            $files = irbisToLines($line);
            foreach ($files as $file) {
                if (!isNullOrEmpty($file)) {
                    array_push($result, $file);
                }
            }
        }

        return $result;
    }

    /**
     * Получение списка серверных процессов.
     *
     * @return array|bool
     * @throws IrbisException
     */
    public function listProcesses() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '+3');
        $response = $this->execute($query);
        $response->checkReturnCode();
        $lines = $response->readRemainingAnsiLines();
        $result = ProcessInfo::parse($lines);

        return $result;
    }

    /**
     * Получение списка терминов с указанным префиксом.
     *
     * @param string $prefix Префикс.
     * @return array Термы (очищенные от префикса).
     * @throws IrbisException
     */
    public function listTerms($prefix) {
        $result = array();

        if (!$this->connected) {
            return $result;
        }

        $prefixLength = strlen($prefix);
        $startTerm = $prefix;
        $lastTerm = $startTerm;
        while (true) {
            $terms = $this->readTerms($startTerm, 512);
            foreach ($terms as $term) {
                $text = $term->text;
                if (strcmp(substr($text, 0, $prefixLength), $prefix)) {
                    break 2;
                }
                if ($text != $startTerm) {
                    $lastTerm = $text;
                    $text = substr($text, $prefixLength);
                    array_push($result, $text);
                }
            }
            $startTerm = $lastTerm;
        }

        return $result;
    }

    /**
     * Пустая операция (используется для периодического
     * подтверждения подключения клиента).
     *
     * @return bool Всегда true при наличии подключения,
     * т. к. код возврата не анализируется.
     * Всегда false при отсутствии подключения.
     */
    public function noOp() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'N');
        $this->execute($query);

        return true;
    }

    /**
     * Разбор строки подключения.
     *
     * @param string $connectionString Строка подключения.
     * @throws IrbisException
     */
    public function parseConnectionString($connectionString) {
        $items = explode(';', $connectionString);
        foreach ($items as $item) {
            if (isNullOrEmpty($item)){
                continue;
            }

            $parts = explode('=', $item, 2);
            if (count($parts) != 2) {
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
                    throw new IrbisException("Unknown key {$name}");
            }
        }
    }

    /**
     * Расформатирование таблицы.
     *
     * @param TableDefinition $definition Определение таблицы.
     * @return bool|string
     */
    public function printTable (TableDefinition $definition) {
        if (!$this->connected) {
            return false;
        }

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
        $result = $response->readRemainingUtfText();

        return $result;
    }

    /**
     * Получение INI-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return IniFile|null
     */
    public function readIniFile($specification) {
        $lines = $this->readTextLines($specification);
        if (!$lines) {
            return null;
        }

        $result = new IniFile();
        $result->parse($lines);

        return $result;
    }

    /**
     * Чтение MNU-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|MenuFile
     */
    public function readMenuFile($specification) {
        $lines = $this->readTextLines($specification);
        if (!$lines) {
            return false;
        }

        $result = new MenuFile();
        $result->parse($lines);

        return $result;
    }

    /**
     * Чтение MNU-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|OptFile
     * @throws IrbisException
     */
    public function readOptFile($specification) {
        $lines = $this->readTextLines($specification);
        if (!$lines) {
            return false;
        }

        $result = new OptFile();
        $result->parse($lines);

        return $result;
    }

    /**
     * Чтение PAR-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|ParFile
     * @throws IrbisException
     */
    public function readParFile($specification) {
        $lines = $this->readTextLines($specification);
        if (!$lines) {
            return false;
        }

        $result = new ParFile();
        $result->parse($lines);

        return $result;
    }

    /**
     * Считывание постингов из поискового индекса.
     *
     * @param PostingParameters $parameters Параметры постингов.
     * @return array|bool Массив постингов.
     * @throws IrbisException
     */
    public function readPostings(PostingParameters $parameters) {
        if (!$this->connected) {
            return false;
        }

        $database = $parameters->database ?: $this->database;
        $query = new ClientQuery($this, 'I');
        $query->addAnsi($database)->newLine();
        $query->add($parameters->numberOfPostings)->newLine();
        $query->add($parameters->firstPosting)->newLine();
        $query->addAnsi($parameters->format)->newLine();
        if (!$parameters->listOfTerms) {
            $query->addUtf($parameters->term)->newLine();
        } else {
            foreach ($parameters->listOfTerms as $term) {
                $query->addUtf($term)->newLine();
            }
        }

        $response = $this->execute($query);
        $response->checkReturnCode(readTermCodes());
        $lines = $response->readRemainingUtfLines();
        $result = TermPosting::parse($lines);

        return $result;
    }

    /**
     * Чтение указанной записи в "сыром" виде.
     *
     * @param string $mfn MFN записи
     * @return bool|RawRecord
     * @throws IrbisException
     */
    public function readRawRecord($mfn) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'C');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode(readRecordCodes());
        $result = new RawRecord();
        $result->decode($response->readRemainingUtfLines());
        $result->database = $this->database;

        return $result;
    }

    /**
     * Чтение указанной записи.
     *
     * @param integer $mfn MFN записи
     * @return bool|MarcRecord
     * @throws IrbisException
     */
    public function readRecord($mfn) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'C');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode(readRecordCodes());
        $result = new MarcRecord();
        $result->decode($response->readRemainingUtfLines());
        $result->database = $this->database;

        return $result;
    }

    /**
     * Чтение указанной версии записи.
     *
     * @param integer $mfn MFN записи
     * @param integer $version Версия записи
     * @return bool|MarcRecord
     * @throws IrbisException
     */
    public function readRecordVersion($mfn, $version) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'C');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $query->add($version);
        $response = $this->execute($query);
        $response->checkReturnCode(readRecordCodes());
        $result = new MarcRecord();
        $result->decode($response->readRemainingUtfLines());
        $result->database = $this->database;

        return $result;
    }

    /**
     * Чтение с сервера нескольких записей.
     *
     * @param array $mfnList Массив MFN.
     * @return array|bool
     * @throws IrbisException
     */
    public function readRecords(array $mfnList) {
        if (!$this->connected) {
            return false;
        }

        if (!$mfnList) {
            return array();
        }

        if (count($mfnList) == 1) {
            return $this->readRecord($mfnList[0]);
        }

        $query = new ClientQuery($this, 'G');
        $query->addAnsi($this->database)->newLine();
        $query->addAnsi(ALL_FORMAT)->newLine();
        $query->add(count($mfnList))->newLine();
        foreach ($mfnList as $mfn) {
            $query->add($mfn)->newLine();
        }
        $response = $this->execute($query);
        $response->checkReturnCode();
        $lines = $response->readRemainingUtfLines();
        $result = array();
        foreach ($lines as $line) {
            $parts = explode('#', $line, 2);
            $parts = explode("\x1F", $parts[1]);
            $parts = array_slice($parts, 1);
            $record = new MarcRecord();
            $record->decode($parts);
            $record->database = $this->database;
            array_push($result, $record);
        }

        return $result;
    }

    /**
     * Загрузка сценариев поиска с сервера.
     *
     * @param string $specification Спецификация.
     * @return array|bool
     */
    public function readSearchScenario($specification) {
        if (!$this->connected) {
            return false;
        }

        $iniFile = $this->readIniFile($specification);
        if (!$iniFile) {
            return false;
        }

        $result = SearchScenario::parse($iniFile);

        return $result;
    }

    /**
     * Простое получение терминов поискового словаря.
     *
     * @param string $startTerm Начальный термин.
     * @param int $numberOfTerms Необходимое количество терминов.
     * @return array|bool
     * @throws IrbisException
     */
    public function readTerms($startTerm, $numberOfTerms=100) {
        $parameters = new TermParameters();
        $parameters->startTerm = $startTerm;
        $parameters->numberOfTerms = $numberOfTerms;

        return $this->readTermsEx($parameters);
    }

    /**
     * Получение терминов поискового словаря.
     *
     * @param TermParameters $parameters Параметры терминов.
     * @return array|bool
     * @throws IrbisException
     */
    public function readTermsEx(TermParameters $parameters) {
        if (!$this->connected) {
            return false;
        }

        $command = $parameters->reverseOrder ? 'P' : 'H';
        $database = $parameters->database ?: $this->database;
        $query = new ClientQuery($this, $command);
        $query->addAnsi($database)->newLine();
        $query->addUtf($parameters->startTerm)->newLine();
        $query->add($parameters->numberOfTerms)->newLine();
        $prepared = prepareFormat($parameters->format);
        $query->addAnsi($prepared)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode(readTermCodes());
        $lines = $response->readRemainingUtfLines();
        $result = TermInfo::parse($lines);

        return $result;
    }

    /**
     * Получение текстового файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|string
     */
    public function readTextFile($specification) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'L');
        $query->addAnsi($specification)->newLine();
        $response = $this->execute($query);
        $result = $response->readAnsi();
        $result = irbisToDos($result);

        return $result;
    }

    /**
     * Получение текстового файла в виде массива строк.
     *
     * @param string $specification Спецификация файла.
     * @return array
     */
    public function readTextLines($specification) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'L');
        $query->addAnsi($specification)->newLine();
        $response = $this->execute($query);
        $result = $response->readAnsi();
        $result = irbisToLines($result);

        return $result;
    }

    /**
     * Чтение TRE-файла с сервера.
     *
     * @param string $specification Спецификация файла.
     * @return bool|TreeFile
     * @throws IrbisException
     */
    public function readTreeFile($specification) {
        $lines = $this->readTextLines($specification);
        if (!$lines) {
            return false;
        }

        $result = new TreeFile();
        $result->parse($lines);

        return $result;
    }

    /**
     * Пересоздание словаря.
     *
     * @param string $database База данных.
     * @return bool
     */
    public function reloadDictionary($database) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'Y');
        $query->addAnsi($database)->newLine();
        $this->execute($query);

        return true;
    }

    /**
     * Пересоздание мастер-файла.
     *
     * @param string $database База данных.
     * @return bool
     */
    public function reloadMasterFile($database) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'X');
        $query->addAnsi($database)->newLine();
        $this->execute($query);

        return true;
    }

    /**
     * Перезапуск сервера (без утери подключенных клиентов).
     *
     * @return bool
     */
    public function restartServer() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '+8');
        $this->execute($query);

        return true;
    }

    /**
     * Простой поиск записей (не более 32 тыс. записей).
     *
     * @param string $expression Выражение для поиска по словарю.
     * @return array|bool
     * @throws IrbisException
     */
    public function search($expression) {
        $parameters = new SearchParameters();
        $parameters->expression = $expression;
        $found = $this->searchEx($parameters);
        $result = FoundLine::toMfn($found);

        return $result;
    }

    /**
     * Поиск всех записей (даже если их окажется больше 32 тыс.).
     *
     * @param string $expression Выражение для поиска по словарю.
     * @return array MFN найденных записей.
     * @throws IrbisException
     */
    public function searchAll($expression) {
        $result = array();
        if (!$this->connected) {
            return $result;
        }

        $firstRecord = 1;

        while (true) {
            $query = new ClientQuery($this, 'K');
            $query->addAnsi($this->database)->newLine();
            $query->addUtf($expression)->newLine();
            $query->add(0)->newLine();
            $query->add($firstRecord)->newLine();
            $response = $this->execute($query);
            $response->checkReturnCode();
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
            if (!$found) {
                break;
            }
            $result = $result + $found;
            $firstRecord += count($found);
            if ($firstRecord >= $totalCount) {
                break;
            }
        }

        return $result;
    }

    /**
     * Определение количества записей,
     * соответствующих поисковому выражению.
     *
     * @param string $expression Поисковое выражение.
     * @return int Количество соответствующих записей.
     * @throws IrbisException
     */
    public function searchCount($expression) {
        if (!$this->connected) {
            return 0;
        }

        $query = new ClientQuery($this, 'K');
        $query->addAnsi($this->database)->newLine();
        $query->addUtf($expression)->newLine();
        $query->add(0)->newLine();
        $query->add(0);
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = $response->readInteger(); // Число найденных записей

        return $result;
    }

    /**
     * Расширенный поиск записей.
     *
     * @param SearchParameters $parameters Параметры поиска.
     * @return array|bool
     * @throws IrbisException
     */
    public function searchEx(SearchParameters $parameters) {
        if (!$this->connected) {
            return false;
        }

        $database = $parameters->database ?: $this->database;
        $query = new ClientQuery($this, 'K');
        $query->addAnsi($database)->newLine();
        $query->addUtf($parameters->expression)->newLine();
        $query->add($parameters->numberOfRecords)->newLine();
        $query->add($parameters->firstRecord)->newLine();
        $prepared = prepareFormat($parameters->format);
        $query->addAnsi($prepared)->newLine();
        $query->add($parameters->minMfn)->newLine();
        $query->add($parameters->maxMfn)->newLine();
        $query->addAnsi($parameters->sequential)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();
        $response->readInteger(); // Число найденных записей.
        $lines = $response->readRemainingUtfLines();
        $result = FoundLine::parse($lines);

        return $result;
    }

    /**
     * Поиск записей с их одновременным считыванием.
     *
     * @param string $expression Поисковое выражение.
     * @param int $limit Максимальное количество загружаемых записей.
     * @return array
     * @throws IrbisException
     */
    public function searchRead($expression, $limit=0) {
        $parameters = new SearchParameters();
        $parameters->expression = $expression;
        $parameters->format = ALL_FORMAT;
        $parameters->numberOfRecords = $limit;
        $found = $this->searchEx($parameters);
        if (!$found) {
            return array();
        }

        $result = array();
        foreach ($found as $item) {
            $lines = explode("\x1F", $item->description);
            $lines = array_slice($lines, 1);
            $record = new MarcRecord();
            $record->decode($lines);
            $record->database = $this->database;
            array_push($result, $record);
        }

        return $result;
    }

    /**
     * Поиск и считывание одной записи, соответствующей выражению.
     * Если таких записей больше одной, то будет считана любая из них.
     * Если таких записей нет, будет возвращен null.
     *
     * @param string $expression Поисковое выражение.
     * @return MarcRecord|null
     * @throws IrbisException
     */
    public function searchSingleRecord($expression) {
        $found = $this->searchRead($expression, 1);
        if (count($found)) {
            return $found[0];
        }

        return null;
    }

    /**
     * Выдача строки подключения для текущего соединения.
     * Соединение не обязательно должно быть установлено.
     *
     * @return string
     */
    public function toConnectionString() {
        return 'host='     . $this->host
            . ';port='     . $this->port
            . ';username=' . $this->username
            . ';password=' . $this->password
            . ';database=' . $this->database
            . ';arm='      . $this->workstation . ';';
    }

    /**
     * Опустошение указанной базы данных.
     *
     * @param string $database База данных.
     * @return bool
     */
    public function truncateDatabase($database) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'S');
        $query->addAnsi($database)->newLine();
        $this->execute($query);

        return true;
    }

    /**
     * Восстановление записи по её MFN.
     *
     * @param integer $mfn MFN восстанавливаемой записи.
     * @return bool|MarcRecord
     * @throws IrbisException
     */
    public function undeleteRecord($mfn) {
        $record = $this->readRecord($mfn);
        if (!$record) {
            return $record;
        }

        if ($record->isDeleted()) {
            $record->status &= ~LOGICALLY_DELETED;
            $this->writeRecord($record);
        }

        return $record;
    }

    /**
     * Разблокирование указанной базы данных.
     *
     * @param string $database База данных.
     * @return bool
     */
    public function unlockDatabase($database) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'U');
        $query->addAnsi($database)->newLine();
        $this->execute($query);

        return true;
    }

    /**
     * Разблокирование записей.
     *
     * @param string $database База данных.
     * @param array $mfnList Массив MFN.
     * @return bool
     */
    public function unlockRecords($database, array $mfnList) {
        if (!$this->connected) {
            return false;
        }

        if (count($mfnList) == 0) {
            return true;
        }

        $database = $database ?: $this->database;
        $query = new ClientQuery($this, 'Q');
        $query->addAnsi($database)->newLine();
        foreach ($mfnList as $mfn) {
            $query->add($mfn)->newLine();
        }

        $this->execute($query);

        return true;
    }

    /**
     * Обновление строк серверного INI-файла
     * для текущего пользователя.
     *
     * @param array $lines Изменённые строки.
     * @return bool
     */
    public function updateIniFile(array $lines) {
        if (!$this->connected) {
            return false;
        }

        if (!$lines) {
            return true;
        }

        $query = new ClientQuery($this, '8');
        foreach ($lines as $line) {
            $query->addAnsi($line)->newLine();
        }

        $this->execute($query);

        return true;
    }

    /**
     * Обновление списка пользователей на сервере.
     *
     * @param array $users Список пользователей.
     * @return bool
     */
    public function updateUserList(array $users) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '+7');
        foreach ($users as $user) {
            $query->addAnsi($user->encode())->newLine();
        }
        $this->execute($query);

        return true;
    }

    /**
     * Сохранение на сервере "сырой" записи.
     *
     * @param RawRecord $record Запись для сохранения.
     * @return bool|int
     * @throws IrbisException
     */
    public function writeRawRecord(RawRecord $record) {
        if (!$this->connected) {
            return false;
        }

        $database = $record->database ?: $this->database;
        $query = new ClientQuery($this, 'D');
        $query->addAnsi($database)->newLine();
        $query->add(0)->newLine();
        $query->add(1)->newLine();
        $query->addUtf($record->encode())->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();

        return $response->returnCode;
    }

    /**
     * Сохранение записи на сервере.
     *
     * @param MarcRecord $record Запись для сохранения (новая или ранее считанная).
     * @param int $lockFlag Оставить запись заблокированной?
     * @param int $actualize Актуализировать словарь?
     * @param bool $dontParse Не разбирать результат.
     * @return bool|integer
     * @throws IrbisException
     */
    public function writeRecord(MarcRecord $record, $lockFlag=0, $actualize=1,
                                $dontParse=false) {
        if (!$this->connected) {
            return false;
        }

        $database = $record->database ?: $this->database;
        $query = new ClientQuery($this, 'D');
        $query->addAnsi($database)->newLine();
        $query->add($lockFlag)->newLine();
        $query->add($actualize)->newLine();
        $query->addUtf($record->encode())->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();
        if (!$dontParse) {
            $record->fields = array();
            $temp = $response->readRemainingUtfLines();
            $lines = array($temp[0]);
            $lines = array_merge($lines, explode(SHORT_DELIMITER, $temp[1]));
            $record->decode($lines);
            $record->database = $database;
        }

        return $response->returnCode;
    }

    /**
     * Сохранение записей на сервере.
     *
     * @param array $records Записи.
     * @param int $lockFlag
     * @param int $actualize
     * @param bool $dontParse
     * @return bool
     * @throws IrbisException
     */
    public function writeRecords(array $records, $lockFlag=0, $actualize=1,
                                 $dontParse=false) {
        if (!$this->connected) {
            return false;
        }

        if (!$records) {
            return true;
        }

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
        $response->getReturnCode();

        if (!$dontParse) {
            $lines = $response->readRemainingUtfLines();
            for ($i = 0; $i < count($records); $i++) {
                $text = $lines[$i];
                if (isNullOrEmpty($text)) {
                    continue;
                }

                $record = $records[$i];
                $record->clear();
                $record->database = $record->database ?: $this->database;
                $recordLines = irbisToLines($text);
                $record->parse($recordLines);
            }
        }

        return true;
    }

    /**
     * Сохранение текстового файла на сервере.
     *
     * @param string $specification Спецификация файла
     * (включая текст файла).
     * @return bool
     */
    public function writeTextFile($specification) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'L');
        $query->addAnsi($specification);
        $this->execute($query);

        return true;
    }
} // class IrbisConnection

final class IrbisUI {

    /**
     * @var IrbisConnection Активное подключение к серверу.
     */
    public $connection;

    /**
     * Конструктор.
     *
     * @param IrbisConnection $connection Активное (!) подключение к серверу.
     * @throws IrbisException
     */
    public function __construct(IrbisConnection $connection) {
        if (!$connection->isConnected()) {
            throw new IrbisException();
        }

        $this->connection = $connection;
    }

    /**
     * Вывод выпадающего списка баз данных.
     *
     * @param string $class
     * @param string $selected
     * @throws IrbisException
     */
    public function listDatabases($class='', $selected='') {
        $dbnnamecat = $this->connection->iniFile->getValue('Main', 'DBNNAMECAT', 'dbnam3.mnu');
        $databases = $this->connection->listDatabases('1..' . $dbnnamecat);
        if (!$databases) {
            throw new IrbisException();
        }

        $classText = '';
        if ($class) {
            $classText = "class='{$class}'";
        }
        echo "<select name='catalogBox' $classText>" . PHP_EOL;
        foreach ($databases as $database) {
            $selectedText = '';
            if (sameString($database->name, $selected)) {
                $selectedText = 'selected';
            }
            echo "<option value='{$database->name}' $selectedText>{$database->description}</option>" . PHP_EOL;
        }
        echo "</select>" . PHP_EOL;
    }

    /**
     * Получение сценариев поиска.
     *
     * @return array
     * @throws IrbisException
     */
    public function getSearchScenario() {
        $ini = $this->connection->iniFile;
        $fileName = $ini->getValue("MAIN", 'SearchIni'); // ???
        $section = $ini->findSection("SEARCH");
        if (!$section) {
            throw new IrbisException();
        }
        $result = SearchScenario::parse($ini);

        return $result;
    }

    /**
     * Вывод выпадающего списка сценариев поиска.
     *
     * @param $name
     * @param $scenarios
     * @param string $class
     * @param int $selectedIndex
     * @param string $selectedValue
     */
    public function listSearchScenario($name, $scenarios, $class='', $selectedIndex=-1,
            $selectedValue='') {
        echo "<select name='$name'>" . PHP_EOL;
        $classText = '';
        if ($class) {
            $classText = " class='$class'";
        }
        $index = 0;
        foreach ($scenarios as $scenario) {
            $selectedText = '';
            if ($selectedValue) {
                if (sameString($scenario->prefix, $selectedValue)) {
                    $selectedText = 'selected';
                }
            } else if ($index == $selectedIndex) {
                $selectedText = 'selected';
            }
            echo "<option value='{$scenario->prefix}' $selectedText $classText>{$scenario->name}</option>" . PHP_EOL;
            $index++;
        }
        echo "</select>" . PHP_EOL;
    }

} // class IrbisUI
