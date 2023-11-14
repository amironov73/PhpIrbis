<?php

//
// Простой JSON-API-адаптер для АБИС ИРБИС64.
// Требует PHP 5.4 или выше.
// Работает с сервером ИРБИС64 2014 и выше.
//

require_once __DIR__ . '/PhpIrbis.php';

$result = null;

$operation = strtolower($_GET['op']);

if (!$operation) {
    echo '<h3 style="color: red;">Не задана операция!</h3>';
    die(1);
}

function db_info()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $result = $connection->getDatabaseInfo($database);
    $connection->disconnect();
    return $result;
}

function get_connection()
{
    $result = new Irbis\Connection();
    $result->host = '127.0.0.1';
    $result->username = 'librarian';
    $result->password = 'secret';
    $result->database = 'IBIS';
    if (!$result->connect()) {
        echo '<h3 style="color: red;">Не удалось подключиться!</h3>';
        echo '<p>', Irbis\describe_error($result->lastError), '</p>';
        die(1);
    }
    return $result;
} // function get_connection

function list_databases()
{
    $connection = get_connection();
    $spec = $_GET['spec'] ?: '1..dbnam2.mnu';
    $result = $connection->listDatabases($spec);
    $connection->disconnect();
    return $result;
} // function list_databases

function list_files()
{
    $connection = get_connection();
    $spec = $_GET['spec'] ?: ('2.' . $connection->database . '.*.*');
    $result = $connection->listFiles($spec);
    $connection->disconnect();
    return $result;
} // function list_files

function list_processes()
{
    $connection = get_connection();
    $result = $connection->listProcesses();
    $connection->disconnect();
    return $result;
} // function list_processes

function list_terms()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $prefix = $_GET['prefix'];
    $result = $connection->listTerms($prefix);
    $connection->disconnect();
    return $result;
} // function list_terms

function max_mfn()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $result = $connection->getMaxMfn($database);
    $connection->disconnect();
    return $result;
} // function max_mfn

function read_menu()
{
    $connection = get_connection();
    $spec = $_GET['spec'];
    $result = $connection->readMenuFile($spec);
    $connection->disconnect();
    return $result;
} // function read_menu

function read_opt()
{
    $connection = get_connection();
    $spec = $_GET['spec'];
    try {
        $result = $connection->readOptFile($spec);
    } catch (\Irbis\IrbisException $e) {
        $result = new Irbis\OptFile();
    }
    $connection->disconnect();
    return $result;
} // function read_opt

function read_raw_record()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $mfn = intval($_GET['mfn']);
    $result = $connection->readRawRecord($mfn);
    $connection->disconnect();
    return $result;
} // function read_raw_record

function read_record()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $mfn = intval($_GET['mfn']);
    $result = $connection->readRecord($mfn);
    $connection->disconnect();
    return $result;
} // function read_record

function read_terms()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $start = $_GET['start'];
    $number = intval($_GET['number'] ?: 100);
    $result = $connection->readTerms($start, $number);
    $connection->disconnect();
    return $result;
} // function read_terms

function read_text_file()
{
    $connection = get_connection();
    $spec = $_GET['spec'];
    $result = $connection->readTextFile($spec);
    $connection->disconnect();
    return $result;
} // function read_text_file

function restart_server()
{
    $connection = get_connection();
    $connection->restartServer();
    $connection->disconnect();
} // function restart_server

function search()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $expression = $_GET['expr'];
    $result = $connection->search($expression);
    $connection->disconnect();
    return $result;
} // function search

function search_count()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $expression = $_GET['expr'];
    $result = $connection->searchCount($expression);
    $connection->disconnect();
    return $result;
} // function search_count

function search_format()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $expression = $_GET['expr'];
    $format = $_GET['format'];
    $parameters = new \Irbis\SearchParameters();
    $parameters->database = $database;
    $parameters->expression = $expression;
    $parameters->format = $format;
    $result = $connection->searchEx($parameters);
    $connection->disconnect();
    $result = \Irbis\FoundLine::toDescription($result);
    sort($result);
    return $result;
} // function search_format

function search_format2()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $expression = $_GET['expr'];
    $format1 = $_GET['format1'];
    $format2 = $_GET['format2'];
    $mfns = $connection->search($expression);
    $result1 = $connection->formatRecords($format1, $mfns);
    $result2 = $connection->formatRecords($format2, $mfns);
    $result = array();
    $mfnCount = count($mfns);
    for ($i = 0; $i < $mfnCount; $i++)
    {
        $item = [
          "description" => $result1[$i],
          "url" => $result2[$i]
        ];
        array_push($result, $item);
    }

    $connection->disconnect();
    return $result;
} // function search_format2

function search_scenarios()
{
    $connection = get_connection();
    $result = \Irbis\SearchScenario::parse($connection->iniFile);
    return $result;
} // function search_scenarios

function server_stat()
{
    $connection = get_connection();
    $result = $connection->getServerStat();
    $connection->disconnect();
    return $result;
} // function server_stat

function server_version()
{
    $connection = get_connection();
    $result = $connection->getServerVersion();
    $connection->disconnect();
    return $result;
} // function server_version

function user_list()
{
    $connection = get_connection();
    $result = $connection->getUserList();
    $connection->disconnect();
    return $result;
} // function user_list

switch ($operation) {
    case 'db_info':
        $result = db_info();
        break;

    case 'list_db':
        $result = list_databases();
        break;

    case 'list_files':
        $result = list_files();
        break;

    case 'list_proc':
        $result = list_processes();
        break;

    case 'list_terms':
        $result = list_terms();
        break;

    case 'max_mfn':
        $result = max_mfn();
        break;

    case 'read':
        $result = read_record();
        break;

    case 'read_menu':
        $result = read_menu();
        break;

    case 'read_opt':
        $result = read_opt();
        break;

    case 'read_raw':
        $result = read_raw_record();
        break;

    case 'read_terms':
        $result = read_terms();
        break;

    case 'read_text':
        $result = read_text_file();
        break;

    case 'restart':
        restart_server();
        break;

    case 'scenarios':
        $result = search_scenarios();
        break;

    case 'search':
        $result = search();
        break;

    case 'search_count':
        $result = search_count();
        break;

    case 'search_format':
        $result = search_format();
        break;

    case 'search_format2':
        $result = search_format2();
        break;

    case 'server_stat':
        $result = server_stat();
        break;

    case 'user_list':
        $result = user_list();
        break;

    case 'version':
        $result = server_version();
        break;

    default:
        echo '<h3 style="color: red;">Неизвестная операция: ', $operation, '</h3>';
        die(1);
        break;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
