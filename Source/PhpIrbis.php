<?php

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

    function readRecord($mfn) {
        if (!$this->connected) {
            return false;
        }

        $packet = $this->header('C');
        $packet = $packet . "\n" . $this->database;
        $packet = $packet . "\n" . $mfn;
        $packet = $this->encode($packet);

        $answer = $this->execute($packet);

        return array_slice($answer, 11);
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
