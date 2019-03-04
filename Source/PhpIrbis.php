<?php

//
// Простой клиент для АБИС ИРБИС64.
//

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
 * Замена переводов строки с ИРБИСных на обычные.
 *
 * @param string $text Текст для замены.
 * @return mixed
 */
function irbisToDos($text) {
    return str_replace("\x1F\x1E", "\n", $text);
}

/**
 * Разбивка текста на строки по ИРБИСным разделителям.
 *
 * @param string $text Текст для разбиения.
 * @return array
 */
function irbisToLines($text) {
    return explode("\x1F\x1E", $text);
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
                else if ($c == "'" || $c == '""' || $c == '|') {
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
}

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

    if ($flag) {
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
}

/**
 * Получение описания по коду ошибки, возвращенному сервером.
 *
 * @param integer $code
 * @return mixed
 */
function describeError($code) {
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
        -801 => 'ERR_GBL_MET',
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

    return $errors[$code];
}

/**
 * @return array "Хорошие" коды для readRecord.
 */
function readRecordCodes() {
    return array(-201, -600, -602, -603);
}

class IrbisException extends Exception {
    public function __construct($message = "",
                                $code = 0,
                                Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

/**
 * Подполе записи. Состоит из кода и значения.
 */
class SubField {
    /**
     * @var string Код подполя.
     */
    public $code;

    /**
     * @var string Значение подполя.
     */
    public $value;

    public function decode($line) {
        $this->code = $line[0];
        $this->value = substr($line, 1);
    }

    public function __toString() {
        return '^' . $this->code . $this->value;
    }
}

/**
 * Поле записи. Состоит из тега и (опционального) значения.
 * Может содержать произвольное количество подполей.
 */
class RecordField {
    public $tag, $value;
    public $subfields = array();

    public function decode($line) {
        $this->tag = strtok($line, "#");
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

    public function __toString() {
        $result = $this->tag . '#' . $this->value;

        foreach ($this->subfields as $sf) {
            $result .= $sf;
        }

        return $result;
    }
}

/**
 * Запись. Состоит из произвольного количества полей.
 */
class MarcRecord {
    public $database, $mfn, $version, $status;
    public $fields = array();

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
            $field = new RecordField();
            $field->decode($line);
            array_push($this->fields, $field);
        }
    }

    public function __toString() {
        $result = $this->mfn . '#' . $this->status . "\x1F\x1E"
            . '0#' . $this->version . "\x1F\x1E";

        foreach ($this->fields as $field) {
            $result .= ($field . "\x1F\x1E");
        }

        return $result;
    }
}

/**
 * Пара строк в меню.
 */
class MenuEntry {
    public $code, $comment;

    public function __toString() {
        return $this->code . ' - ' . $this->comment;
    }
}

/**
 * Файл меню. Состоит из пар строк (см. MenuEntry).
 */
class MenuFile {
    public $entries = array();

    public function getEntry($code) {
        // TODO implement
        return false;
    }

    public function getValue($code) {
        // TODO implement
        return false;
    }

    public function parse(array $lines) {
        // TODO implement
    }

    public function __toString() {
        $result = '';

        foreach ($this->entries as $entry) {
            $result .= ($entry . PHP_EOL);
        }

        return $result;
    }
}

/**
 * Строка INI-файла. Состоит из ключа
 * и (опционального значения).
 */
class IniLine {
    public $key, $value;

    public function __toString() {
        return $this->key . ' = ' . $this->value;
    }
}

/**
 * Секция INI-файла. Состоит из строк
 * (см. IniLine).
 */
class IniSection {
    public $name = '';
    public $lines = array();

    public function __toString() {
        $result = '[' . $this->name . ']' . PHP_EOL;

        foreach ($this->lines as $line) {
            $result .= ($line . PHP_EOL);
        }

        return $result;
    }
}

/**
 * INI-файл. Состоит из секций (см. IniSection).
 */
class IniFile {
    public $sections = array();

    public function parse(array $lines) {
        // TODO implement
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
}

/**
 * Информация о базе данных ИРБИС.
 */
class DatabaseInfo {
    public $name, $description, $maxMfn;
    public $logicallyDeletedRecords;
    public $physicallyDeletedRecords;
    public $nonActualizedRecords;
    public $lockedRecords;
    public $databaseLocked, $readOnly;

    public function parse(array $lines) {
        // TODO implement
    }

    public function __toString() {
        return $this->name;
    }
}

/**
 * Информация о запущенном на ИРБИС-сервере процессе.
 */
class ProcessInfo {
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
}

/**
 * Информация о версии ИРБИС-сервера.
 */
class VersionInfo {
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
}

/**
 * Информация о клиенте, подключенном к серверу ИРБИС
 * (не обязательно о текущем).
 */
class ClientInfo {
    public $number;
    public $ipAddress;
    public $port;
    public $name;
    public $id;
    public $workstation;
    public $registered;
    public $acknowledged;
    public $lastCommand;
    public $commandNumber;

    public function parse(array $lines) {
        // TODO implement
    }

    public function __toString() {
        return $this->ipAddress;
    }
}

/**
 * Информация о зарегистрированном пользователе системы
 * (по данным client_m.mnu).
 */
class UserInfo {
    public $number;
    public $name;
    public $password;
    public $cataloger;
    public $reader;
    public $circulation;
    public $acquisitions;
    public $provision;
    public $administrator;

    public function parse(array $lines) {
        // TODO implement
    }

    public function __toString() {
        return $this->name;
    }
}

/**
 * Данные для команды TableCommand
 */
class TableDefinition {
    public $database;
    public $table;
    public $headers = array();
    public $mode;
    public $searchQuery;
    public $minMfn;
    public $maxMfn;
    public $sequentialQuery;
    public $mfnList;

    public function __toString() {
        return $this->table;
    }
}

/**
 * Статистика работы ИРБИС-сервера.
 */
class ServerStat {
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

    public function parse(array $lines) {
        // TODO implement
    }

    public function __toString() {
        // TODO implement
        return '';
    }
}

/**
 * Параметры для запроса постингов с сервера.
 */
class PostingParameters {
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
     * @var string Терм.
     */
    public $term = '';

    /**
     * @var array Список термов.
     */
    public $listOfTerms = array();
}

/**
 * Параметры для запроса термов с сервера.
 */
class TermParameters {
    /**
     * @var string Имя базы данных.
     */
    public $database = '';

    /**
     * @var int Количество считываемых термов.
     */
    public $numberOfTerms = 0;

    /**
     * @var bool Возвращать в обратном порядке.
     */
    public $reverseOrder = false;

    /**
     * @var string Начальный терм.
     */
    public $startTerm = '';

    /**
     * @var string Формат.
     */
    public $format = '';
}

/**
 * Параметры для поиска записей.
 */
class SearchParameters {
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
}

/**
 * Клиентский запрос.
 */
class ClientQuery {
    private $accumulator = '';

    public function __construct(IrbisConnection $connection, $command) {
        $this->addAnsi($command)->newLine();
        $this->addAnsi($connection->arm)->newLine();
        $this->addAnsi($command)->newLine();
        $this->addAnsi($connection->clientId)->newLine();
        $this->addAnsi($connection->queryId)->newLine();
        $this->addAnsi($connection->password)->newLine();
        $this->addAnsi($connection->username)->newLine();
        $this->newLine();
        $this->newLine();
        $this->newLine();
    }

    public function add($value) {
        $this->addAnsi(strval($value));

        return $this;
    }

    public function addAnsi($value) {
        $converted = mb_convert_encoding($value, 'Windows-1251');
        $this->accumulator .= $converted;

        return $this;
    }

    public function addUtf($value) {
        $this->accumulator .= $value;

        return $this;
    }

    public function newLine() {
        $this->accumulator .= chr(10);

        return $this;
    }

    public function __toString() {
        return strlen($this->accumulator) . chr(10) . $this->accumulator;
    }
}

/**
 * Ответ сервера.
 */
class ServerResponse {
    public $command = '';
    public $clientId = 0;
    public $queryId = 0;
    public $returnCode = 0;

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
        for ($i=0; $i < 7; $i++) {
            $this->readAnsi();
        }
    }

    /**
     * Проверка кода возврата.
     *
     * @param array $goodCodes Разрешенные коды возврата.
     * @throws Exception
     */
    public function checkReturnCode(array $goodCodes=array()) {
        if ($this->getReturnCode() < 0) {
            if (!in_array($this->returnCode, $goodCodes)) {
                throw new IrbisException(describeError($this->returnCode),$this->returnCode);
            }
        }
    }

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

    public function getReturnCode() {
        $this->returnCode = $this->readInteger();
        return $this->returnCode;
    }

    public function readAnsi() {
        $result = $this->getLine();
        $result = mb_convert_encoding($result, 'UTF-8', 'Windows-1251');

        return $result;
    }

    public function readInteger() {
        $line = $this->getLine();

        return intval($line);
    }

    public function readRemainingAnsiLines() {
        $result = array();

        while($this->offset < $this->answerLength) {
            $line = $this->readAnsi();
            array_push($result, $line);
        }

        return $result;
    }

    public function readRemainingAnsiText() {
        $result = substr($this->answer, $this->offset);
        $result = mb_convert_encoding($result, mb_internal_encoding(), 'Windows-1251');

        return $result;
    }

    public function readRemainingUtfLines() {
        $result = array();

        while($this->offset < $this->answerLength) {
            $line = $this->readUtf();
            array_push($result, $line);
        }

        return $result;
    }

    public function readRemainingUtfText() {
        $result = substr($this->answer, $this->offset);

        return $result;
    }

    public function readUtf() {
        return $this->getLine();
    }
}

/**
 * Подключение к ИРБИС-серверу.
 */
class IrbisConnection {
    public $host = '127.0.0.1', $port = 6666;
    public $username = '', $password = '';
    public $database = 'IBIS', $arm = 'C';
    public $clientId = 0;
    public $queryId = 0;

    private $connected = false;

    //================================================================

    /**
     * Актуализация записи с указанным MFN.
     *
     * @param string $database Имя базы данных.
     * @param integer $mfn MFN, подлежащий актуализации.
     * @return bool
     * @throws Exception
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
     * @throws Exception
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

        $response = $this->execute($query);
        $response->getReturnCode();
        if ($response->returnCode == -3337) {
            goto AGAIN;
        }

        if ($response->returnCode < 0) {
            return false;
        }

        $this->connected = true;

        return true;
    }

    /**
     * Создание базы данных.
     *
     * @param string $database Имя создаваемой базы.
     * @param string $description Описание в свободной форме.
     * @param int $readerAccess Читатель будет иметь доступ?
     * @return bool
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * Удаление записи по её MFN.
     *
     * @param integer $mfn MFN удаляемой записи.
     * @throws Exception
     */
    public function deleteRecord($mfn) {
        $record = $this->readRecord($mfn);
        $record->status |= 1;
        $this->writeRecord($record);
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
        socket_write($socket, $packet, strlen($packet));
        $response = new ServerResponse($socket);
        $this->queryId++;

        return $response;
    }

    /**
     * Форматирование записи с указанным MFN.
     *
     * @param string $format Текст формата
     * @param integer $mfn MFN записи
     * @return bool|string
     * @throws Exception
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
     * Получение информации о базе данных.
     *
     * @param string $database Имя базы данных.
     * @return bool|DatabaseInfo
     */
    public function getDatabaseInfo($database) {
        if (!$this->connected) {
            return false;
        }

        // TODO implement

        return new DatabaseInfo();
    }

    /**
     * Получение максимального MFN для указанной базы данных.
     *
     * @param string $database Имя базы данных.
     * @return bool|integer
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function getUserList() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, '+9');
        $response = $this->execute($query);
        $response->checkReturnCode();

        // TODO implement

        return array();
    }

    /**
     * Получение списка баз данных с сервера.
     *
     * @param string $specification Спецификация файла со списком баз.
     * @return array|bool
     */
    public function listDatabases($specification) {
        if (!$this->connected) {
            return false;
        }

        // TODO implement

        return array();
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
     * @throws Exception
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
     * Пустая операция (используется для периодического
     * подтверждения подключения клиента).
     *
     * @return bool
     */
    public function noOp() {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'N');
        $this->execute($query);

        return true;
    }

    public function readMenu($specification) {
        $text = $this->readTextFile($specification);
        if (!$text) {
            return false;
        }

        $lines = explode("\x1F\x1E", $text);
        $result = new MenuFile();
        $result->parse($lines);

        return $result;
    }

    /**
     * Разбор строки подключения.
     *
     * @param string $connectionString Строка подключения.
     */
    public function parseConnectionString($connectionString) {
        // TODO implement
    }

    /**
     * Расформатирование таблицы.
     *
     * @param TableDefinition $definition Определение таблицы
     * @return bool|string
     */
    public function printTable (TableDefinition $definition) {
        if (!$this->connected) {
            return false;
        }

        // TODO implement

        return '';
    }

    /**
     * Чтение указанной записи.
     *
     * @param string $mfn MFN записи
     * @return bool|MarcRecord
     * @throws Exception
     */
    public function readRecord($mfn) {
        if (!$this->connected) {
            return false;
        }

        $query = new ClientQuery($this, 'C');
        $query->addAnsi($this->database)->newLine();
        $query->add($mfn)->newLine();
        $response = $this->execute($query);
        // TODO добавить разрешенные коды
        $response->checkReturnCode();
        $result = new MarcRecord();
        $result->decode($response->readRemainingUtfLines());

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

        // TODO implement

        return array();
    }

    /**
     * Простое получение термов поискового словаря.
     *
     * @param string $startTerm Начальный терм.
     * @param int $numberOfTerms Необходимое количество термов.
     * @return array|bool
     */
    public function readTerms($startTerm, $numberOfTerms=100) {
        $parameters = new TermParameters();
        $parameters->startTerm = $startTerm;
        $parameters->numberOfTerms = $numberOfTerms;

        return $this->readTermsEx($parameters);
    }

    /**
     * Получение термов поискового словаря.
     *
     * @param TermParameters $parameters Параметры термов.
     * @return array|bool
     */
    public function readTermsEx(TermParameters $parameters) {
        if (!$this->connected) {
            return false;
        }

        $command = 'H';
        if ($parameters->reverseOrder) {
            $command = 'P';
        }

        $database = $parameters->database;
        if (isNullOrEmpty($database)) {
            $database = $this->database;
        }

        $query = new ClientQuery($this, $command);
        $query->addAnsi($database)->newLine();
        $query->addUtf($parameters->startTerm)->newLine();
        $query->add($parameters->numberOfTerms)->newLine();
        $query->addAnsi($parameters->format)->newLine();
        $response = $this->execute($query);
        // TODO добавить обработку разрешенных кодов
        $response->getReturnCode();
        $result = $response->readRemainingUtfLines();

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
     * Простой поиск записей.
     *
     * @param string $expression Выражение для поиска по словарю.
     * @return array|bool
     * @throws Exception
     */
    public function search($expression) {
        $parameters = new SearchParameters();
        $parameters->expression = $expression;

        return $this->searchEx($parameters);
    }

    /**
     * Поиск записей.
     *
     * @param SearchParameters $parameters Параметры поиска.
     * @return array|bool
     * @throws Exception
     */
    public function searchEx(SearchParameters $parameters) {
        if (!$this->connected) {
            return false;
        }

        $database = $parameters->database;
        if (isNullOrEmpty($database)) {
            $database = $this->database;
        }

        $query = new ClientQuery($this, 'K');
        $query->addAnsi($database)->newLine();
        $query->addUtf($parameters->expression)->newLine();
        $query->add($parameters->numberOfRecords)->newLine();
        $query->add($parameters->firstRecord)->newLine();
        $prepared = prepareFormat($parameters->format);
        $query->addAnsi($prepared)->newLine();
        $query->addAnsi($parameters->minMfn)->newLine();
        $query->addAnsi($parameters->maxMfn)->newLine();
        $query->addAnsi($parameters->sequential)->newLine();
        $response = $this->execute($query);
        $response->checkReturnCode();
        $result = $response->readRemainingUtfLines();

        return $result;
    }

    /**
     * Выдача строки подключения для текущего соединения.
     *
     * @return string
     */
    public function toConnectionString() {
        return 'host='     . $this->host
            . ';port='     . $this->port
            . ';username=' . $this->username
            . ';password=' . $this->password
            . ';database=' . $this->database
            . ';arm='      . $this->arm . ';';
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

        // TODO implement

        return true;
    }

    /**
     * Сохранение записи на сервере.
     *
     * @param MarcRecord $record Запись для сохранения (новая или ранее считанная).
     * @param int $lockFlag Оставить запись заблокированной?
     * @param int $actualize Актуализировать словарь?
     * @return bool
     */
    public function writeRecord(MarcRecord $record, $lockFlag=0, $actualize=1) {
        if (!$this->connected) {
            return false;
        }

        $database = $record->database;
        if (!$database) {
            $database = $this->database;
        }

        $query = new ClientQuery($this, 'D');
        $query->addAnsi($database)->newLine();
        $query->add($lockFlag)->newLine();
        $query->add($actualize)->newLine();
        // TODO implement properly
        $query->addUtf(strval($record));

        return true;
    }
}
