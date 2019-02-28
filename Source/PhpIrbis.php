<?php

//
// Простой клиент для АБИС ИРБИС64.
//

/**
 * Подполе записи. Состоит из кода и значения.
 */
class SubField {
    public $code, $value;

    function decode($line) {
        $this->code = $line[0];
        $this->value = substr($line, 1);
    }

    function encode() {
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

    function decode($line) {
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

    function encode() {
        $result = $this->tag . '#' . $this->value;

        foreach ($this->subfields as $sf) {
            $result .= $sf->encode();
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

    function decode($lines) {
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

    function encode() {
        $result = $this->mfn . '#' . $this->status . "\x1F\x1E"
            . '0#' . $this->version . "\x1F\x1E";

        foreach ($this->fields as $field) {
            $result .= ($field->encode() . "\x1F\x1E");
        }

        return $result;
    }
}

/**
 * Пара строк в меню.
 */
class MenuEntry {
    public $code, $comment;
}

/**
 * Файл меню. Состоит из пар строк (см. MenuEntry).
 */
class MenuFile {
    public $entries = array();

    function getEntry($code) {
        return false;
    }

    function getValue($code) {
        return false;
    }

    function parse($lines) {
        // TODO implement
    }
}

/**
 * Строка INI-файла. Состоит из ключа
 * и (опционального значения).
 */
class IniLine {
    public $key, $value;
}

/**
 * Секция INI-файла. Состоит из строк
 * (см. IniLine).
 */
class IniSection {
    public $lines = array();
}

/**
 * INI-файл. Состоит из секций (см. IniSection).
 */
class IniFile {
    public $sections = array();
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

    function parse($lines) {
        // TODO implement
    }
}

/**
 * Информация о запущенном на ИРБИС-сервере процессе.
 */
class ProcessInfo {
    public $number, $ipAddress, $name,
        $clientId, $workstation, $started,
        $lastCommand, $commandNumber,
        $processId, $state;

    function parse($lines) {
        // TODO implement
    }
}

/**
 * Информация о версии ИРБИС-сервера.
 */
class VersionInfo {
    public $organization;
    public $version;
    public $maxClients;
    public $connectedClients;
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
}

/**
 * Подключение к ИРБИС-серверу.
 */
class IrbisConnection {
    public $host = '127.0.0.1', $port = 6666;
    public $username = '', $password = '';
    public $database = 'IBIS', $arm = 'C';

    private $connected = false;
    private $queryId;
    private $clientId;

    //================================================================

    function actualizeRecord($databaseName, $mfn) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('F');
        $packet = $packet . "\n" . $databaseName;
        $packet = $packet . "\n" . $mfn;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function connect() {
        if ($this->connected) {
            return true;
        }

        $this->clientId = rand(100000, 900000);
        $this->queryId = 1;
        $packet = $this->header('A');
        $packet = $packet . "\n" . $this->username;
        $packet = $packet . "\n" . $this->password;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        $this->connected = true;

        return true;
    }

    function createDatabase($databaseName, $description, $readerAccess) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('T');
        $packet = $packet . "\n" . $databaseName;
        $packet = $packet . "\n" . $description;
        $packet = $packet . "\n" . $readerAccess;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function createDictionary($databaseName) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('Z');
        $packet = $packet . "\n" . $databaseName;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function deleteDatabase($databaseName) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('W');
        $packet = $packet . "\n" . $databaseName;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function disconnect() {
        if (!$this->connected) {
            return true;
        }

        $packet = $this->header('B');
        $packet = $packet . "\n" . $this->username;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        $this->connected = false;

        return true;
    }

    function formatRecord($format, $mfn) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('G');
        $packet = $packet . "\n" . $this->database;
        $packet = $packet . "\n" . $format;
        $packet = $packet . "\n" . '1';
        $packet = $packet . "\n" . $mfn;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        return $answer[11];
    }

    function getMaxMfn($databaseName) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('O');
        $packet = $packet . "\n" . $databaseName;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        return intval($answer[10]);
    }

    function listFiles($specification) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('!');
        $packet = $packet . "\n" . $specification;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        return array_slice($answer, 10);
    }

    function noOp() {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('N');
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function readMenu($specification) {
        $text = $this->readTextFile($specification);
        if (!$text) {
            return false;
        }

        $lines = explode("\x1F\x1E", $text);
        $result = new MenuFile();
        $result->parse($lines);

        return $result;
    }

    function readRecord($mfn) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('C');
        $packet = $packet . "\n" . $this->database;
        $packet = $packet . "\n" . $mfn;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);
        $answer = array_slice($answer, 11);
        $result = new MarcRecord();
        $result->decode($answer);

        return $result;
    }

    function readTerms($startTerm, $numberOfTerms=10) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('H');
        $packet = $packet . "\n" . $this->database;
        $packet = $packet . "\n" . $startTerm;
        $packet = $packet . "\n" . $numberOfTerms;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        return array_slice($answer, 11);
    }

    function readTextFile($specification) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('L');
        $packet = $packet . "\n" . $specification;

        $answer = $this->execute($packet);

        return $answer[11];
    }

    function reloadDictionary($databaseName) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('Y');
        $packet = $packet . "\n" . $databaseName;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function reloadMasterFile($databaseName) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('X');
        $packet = $packet . "\n" . $databaseName;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function restartServer() {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('+8');
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function search($expression) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('K');
        $packet = $packet . "\n" . $this->database;
        $packet = $packet . "\n" . $expression;
        $packet = $packet . "\n" . '0';
        $packet = $packet . "\n" . '1';
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        return array_slice($answer, 11);
    }

    function truncateDatabase($databaseName) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('S');
        $packet = $packet . "\n" . $databaseName;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function unlockDatabase($databaseName) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('U');
        $packet = $packet . "\n" . $databaseName;
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function updateIniFile($lines) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('8');
        $packet = $packet . "\n" . implode("\n", $lines);
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    function writeRecord($database, $record) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('D');
        $packet = $packet . "\n" . $database;
        $packet = $packet . "\n" . '0';
        $packet = $packet . "\n" . '1';
        $packet = $packet . "\n" . implode("\n", $record);
        $packet = $this->encode($packet);

        $this->execute($packet);

        return true;
    }

    //======================================================================

    function header($commandCode) {
        $packet = implode("\n", array
            (
                $commandCode,
                $this->arm,
                $commandCode,
                $this->clientId,
                $this->queryId,
                $this->password,
                $this->username,
                '',
                '',
                ''
            ));

        return $packet;
    }

    function encode($packet) {
        $packet = strlen($packet) . "\n" . $packet;

        return $packet;
    }

    function execute($packet) {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return false;
        }

        if (!socket_connect($socket, $this->host, $this->port)) {
            socket_close($socket);
            return false;
        }

        $this->queryId++;

        socket_write($socket, $packet, strlen($packet));
        $answer = '';
        while ($buf = socket_read($socket, 2048)) {
            $answer .= $buf;
        }

        socket_close($socket);

        return explode("\r\n", $answer);
    }

}
