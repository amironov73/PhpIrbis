<?php

//
// Простой JSON-API-адаптер для АБИС ИРБИС64.
// Требует PHP 5.4 или выше.
// Работает с сервером ИРБИС64 2014 и выше.
//

use Irbis\Connection;
use Irbis\DatabaseInfo;
use Irbis\FoundLine;
use Irbis\IrbisException;
use Irbis\MarcRecord;
use Irbis\MenuFile;
use Irbis\OptFile;
use Irbis\RawRecord;
use Irbis\SearchParameters;
use Irbis\SearchScenario;
use Irbis\VersionInfo;

require_once __DIR__ . '/PhpIrbis.php';

$result = null;

if (empty($_GET['op'])) {
    echo '<h3 style="color: red;">Не задана операция!</h3>';
    die(1);
}

$operation = strtolower($_GET['op']);

function get_param($name, $value = null) {
    $result = $value;
    if (!empty($_GET[$name])) {
        $result = $_GET[$name];
    }
    return $result;
}

function get_db($connection) {
    $result = $connection->database;
    if (!empty($_GET['db'])) {
        $result = $_GET['db'];
    }

    return $result;
}

/**
 * Получение информации о базе данных.
 * @return DatabaseInfo Информация о базе данных.
 */
function db_info()
{
    $connection = get_connection();
    $database = get_db($connection);
    $result = $connection->getDatabaseInfo($database);
    $connection->disconnect();
    return $result;
}

/**
 * Подключение к серверу ИРБИС64.
 * @return Connection Активное подключение к серверу ИРБИС64.
 * При невозможности подключения к серверу скрипт умирает <code>die(1)</code>.
 */
function get_connection()
{
    $result = new Connection();
    $result->host = getenv ('IRBIS_HOST') ?: '127.0.0.1';
    $result->port = (int) (getenv ('IRBIS_PORT') ?: '6666');
    $result->username = getenv ('IRBIS_USER') ?: 'librarian';
    $result->password = getenv ('IRBIS_PASSWORD') ?: 'secret';
    $result->database = getenv ('IRBIS_DATABASE)') ?: 'IBIS';
    if (!$result->connect()) {
        echo '<h3 style="color: red;">Не удалось подключиться!</h3>';
        echo '<p>', Irbis\describe_error($result->lastError), '</p>';
        die(1);
    }
    return $result;
} // function get_connection

/**
 * Получение списка баз данных.
 * @return array Массив описаний баз данных.
 */
function list_databases()
{
    $connection = get_connection();
    $spec = get_param('spec', '1..dbnam2.mnu');
    $result = $connection->listDatabases($spec);
    $connection->disconnect();
    return $result;
} // function list_databases

/**
 * Получение списка серверных ресурсов.
 * @return array Список найденных ресурсов.
 */
function list_files()
{
    $connection = get_connection();
    $spec = get_param('spec', '2.' . $connection->database . '.*.*');
    $result = $connection->listFiles($spec);
    $connection->disconnect();
    return $result;
} // function list_files

/**
 * Получение списка рабочих серверных процессов.
 * @return array Массив описаний серверных процессов.
 */
function list_processes()
{
    $connection = get_connection();
    $result = $connection->listProcesses();
    $connection->disconnect();
    return $result;
} // function list_processes

/**
 * Получение списка поисковых терминов.
 * @return array Массив поисковых терминов.
 */
function list_terms()
{
    $connection = get_connection();
    $database = get_db($connection);
    $connection->database = $database;
    $prefix = get_param('prefix', '');
    $result = $connection->listTerms($prefix);
    $connection->disconnect();
    return $result;
} // function list_terms

/**
 * Получение максимального MFN для указанной базы данных.
 * @return int Максимальный MFN.
 */
function max_mfn()
{
    $connection = get_connection();
    $database = get_db($connection);
    $result = $connection->getMaxMfn($database);
    $connection->disconnect();
    return $result;
} // function max_mfn

/**
 * Получение меню.
 * @return MenuFile Меню.
 */
function read_menu()
{
    $connection = get_connection();
    $spec = $_GET['spec'];
    $result = $connection->readMenuFile($spec);
    $connection->disconnect();
    return $result;
} // function read_menu

/**
 * Получение файла оптимизации форматов.
 * @return OptFile Файл оптимизации форматов.
 */
function read_opt()
{
    $connection = get_connection();
    $spec = $_GET['spec'];
    try {
        $result = $connection->readOptFile($spec);
    } catch (IrbisException $e) {
        $result = new Irbis\OptFile();
    }
    $connection->disconnect();
    return $result;
} // function read_opt

/**
 * Чтение библиографической записи в сыром формате.
 * @return RawRecord Запись в сыром формате.
 */
function read_raw_record()
{
    $connection = get_connection();
    $database = get_db($connection);
    $connection->database = $database;
    $mfn = (int) get_param('mfn', '0');
    $result = $connection->readRawRecord($mfn);
    $connection->disconnect();
    return $result;
} // function read_raw_record

/**
 * Чтение библиографической записи в структурированном формате.
 * @return MarcRecord Запись в структурированном формате.
 */
function read_record()
{
    $connection = get_connection();
    $database = get_db($connection);
    $connection->database = $database;
    $mfn = (int) get_param('mfn', '0');
    $result = $connection->readRecord($mfn);
    $connection->disconnect();
    return $result;
} // function read_record

/**
 * Чтение массива поисковых терминов.
 * @return array Массив поисковых терминов.
 */
function read_terms()
{
    $connection = get_connection();
    $database = $_GET['db'] ?: $connection->database;
    $connection->database = $database;
    $start = $_GET['start'];
    $number = (int) ($_GET['number'] ?: 100);
    $result = $connection->readTerms($start, $number);
    $connection->disconnect();
    return $result;
} // function read_terms

/**
 * Чтение текстового ресурса с сервера.
 * @return string Текстовый ресурс.
 */
function read_text_file()
{
    $connection = get_connection();
    $spec = get_param('spec');
    $result = $connection->readTextFile($spec);
    $connection->disconnect();
    return $result;
} // function read_text_file

/**
 * Перезапуск сервера.
 * @return void
 */
function restart_server()
{
    $connection = get_connection();
    $connection->restartServer();
    $connection->disconnect();
} // function restart_server

/**
 * Поиск библиографических записей.
 * @return array Массив MFN найденных записей.
 */
function search()
{
    $connection = get_connection();
    $database = get_db($connection);
    $connection->database = $database;
    $expression = get_param('expr', '');
    $result = $connection->search($expression);
    $connection->disconnect();
    return $result;
} // function search

/**
 * Получение количества записей, удовлетворяющих поисковому запросу.
 * @return int Количество найденных записей.
 */
function search_count()
{
    $connection = get_connection();
    $database = get_db($connection);
    $connection->database = $database;
    $expression = get_param('expr','');
    $result = $connection->searchCount($expression);
    $connection->disconnect();
    return $result;
} // function search_count

/**
 * Поиск с форматированием.
 * @return array Массив форматированных записей.
 */
function search_format()
{
    $connection = get_connection();
    $database = get_db($connection);
    $expression = get_param('expr','');
    $format = get_param('format', '@brief');
    $parameters = new SearchParameters();
    $parameters->database = $database;
    $parameters->expression = $expression;
    $parameters->format = $format;
    $result = $connection->searchEx($parameters);
    $connection->disconnect();
    $result = FoundLine::toDescription($result);
    sort($result);
    return $result;
} // function search_format

/**
 * Поиск полнотекстовых документов.
 * @return array Массив объектов, состоящих из форматированной записи
 * и путь к полному тексту документа.
 */
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
        $result[] = $item;
    }

    $connection->disconnect();
    return $result;
} // function search_format2

/**
 * Получение поисковых сценариев по умолчанию.
 * @return array Массив поисковых сценариев.
 */
function search_scenarios()
{
    $connection = get_connection();
    return SearchScenario::parse($connection->iniFile);
} // function search_scenarios

function server_stat()
{
    $connection = get_connection();
    $result = $connection->getServerStat();
    $connection->disconnect();
    return $result;
} // function server_stat

/**
 * Получение версии сервера ИРБИС64.
 * @return VersionInfo Версия сервера.
 */
function server_version()
{
    $connection = get_connection();
    $result = $connection->getServerVersion();
    $connection->disconnect();
    return $result;
} // function server_version

/**
 * Получение списка зарегистрированных в системе пользователей.
 * @return array Массив пользователей.
 */
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
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
