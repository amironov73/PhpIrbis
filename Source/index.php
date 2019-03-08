<?php

require_once ('PhpIrbis.php');

function dumpArray($arr) {
    echo "<ol>";
    if ($arr) {
        foreach ($arr as $item) {
            echo "<li>$item</li>";
        }
    }
    echo "</ol>";
}

$connection = new IrbisConnection();
$connection->username = '1';
$connection->password = '1';
$connection->arm = 'A';

if (!$connection->connect()) {
    echo "Не удалось подключиться!";
    die(1);
}

$ini = $connection->iniFile;
echo "<p>VERSION: {$connection->serverVersion}<br/>";
echo "INTERVAL: {$connection->interval}<br/>";
$dbnnamecat = $ini->getValue('Main', 'DBNNAMECAT');
echo "DBNAMECAT: {$dbnnamecat}</p>";

//$connection->noOp();
//echo '<p>NO OP</p>';

$version = $connection->getServerVersion();
echo "<p>Версия: {$version->version} {$version->organization}</p>";

//$processes = $connection->listProcesses();
//dumpArray($processes);
//
//$databases = $connection->listDatabases();
//echo "<p>";
//foreach ($databases as $db) {
//    echo "{$db->name} {$db->description}<br/>";
//}
//echo "</p>";

//$database = $connection->getDatabaseInfo();
//echo "<p>LOGICALLY DELETED: ", implode(', ', $database->logicallyDeletedRecords), "</p>";

//$users = $connection->getUserList();
//echo "<p>";
//dumpArray($users);
//echo "</p>";

//$stat = $connection->getServerStat();
//echo "<p>";
//dumpArray($stat->runningClients);
//echo "</p>";

//$maxMfn = $connection->getMaxMfn($connection->database);
//echo "<p>MAX MFN: $maxMfn</p>";

//$formatted = $connection->formatRecord("@brief", 123);
//echo "<p>FORMATTED: $formatted</p>";

//$formatted = $connection->formatRecords("@brief", array(1, 2, 3));
//dumpArray($formatted);

//$files = $connection->listFiles("3.IBIS.brief.*");
//echo '<p>' . implode(', ', $files) . '</p>';

//$found = $connection->search("K=ALG$");
//echo '<p>ALG$</p>';
//echo '<p>' . implode(', ', $found) . '</p>';

//$found = $connection->search("K=БЕТОН$");
//echo '<p>БЕТОН</p>';
//echo '<p>' . implode(', ', $found) . '</p>';

//$record = $connection->readRecord(123);
//echo '<p>' .  $record->mfn . ' => ' . count($record->fields)  . '</p>';

//$terms = $connection->readTerms("K=");
//echo '<p>' . implode('<br/>', $terms) . '</p>';

//$content = $connection->readTextFile("3.IBIS.WS.OPT");
//echo '<p>' . $content . '</p>';

//$records = $connection->readRecords(array(10, 20, 30));
//dumpArray($records);

//$records = $connection->searchRead("K=ALG$", 10);
//dumpArray($records);

//$record = new MarcRecord();
//$record->add(100, 'Field100/1');
//$record->add(100, 'Field100/2');
//$record->add(200)->add('a', 'SubA')->add('b', 'SubB');
//$record->add(920, 'PAZK');
//$connection->writeRecord($record);
//echo "<p>{$record}</p>";

$tree = $connection->readTreeFile("3.IBIS.II.TRE");
dumpArray($tree->roots);

$connection->disconnect();

echo '<p>ALL DONE</p>';
