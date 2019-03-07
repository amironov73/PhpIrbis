<?php

require_once ('PhpIrbis.php');

function dumpArray($arr) {
    echo "<p>";
    if ($arr) {
        foreach ($arr as $item) {
            echo "$item<br/>";
        }
    }
    echo "</p>";
}

$connection = new IrbisConnection();
$connection->username = '1';
$connection->password = '1';
$connection->arm = 'A';

$connection->connect();

$connection->noOp();
echo '<p>NO OP</p>';

$version = $connection->getServerVersion();
echo "<p>Версия: {$version->version} {$version->organization}</p>";

$processes = $connection->listProcesses();
dumpArray($processes);

$databases = $connection->listDatabases();
echo "<p>";
foreach ($databases as $db) {
    echo "{$db->name} {$db->description}<br/>";
}
echo "</p>";

$database = $connection->getDatabaseInfo();
echo "<p>LOGICALLY DELETED: ", implode(', ', $database->logicallyDeletedRecords), "</p>";

$users = $connection->getUserList();
echo "<p>";
dumpArray($users);
echo "</p>";

$stat = $connection->getServerStat();
echo "<p>";
dumpArray($stat->runningClients);
echo "</p>";

$maxMfn = $connection->getMaxMfn($connection->database);
echo "<p>MAX MFN: $maxMfn</p>";

$formatted = $connection->formatRecord("@brief", 123);
echo "<p>FORMATTED: $formatted</p>";

$files = $connection->listFiles("3.IBIS.brief.*");
echo '<p>' . implode(', ', $files) . '</p>';

$found = $connection->search("K=ALG$");
echo '<p>ALG$</p>';
echo '<p>' . implode(', ', $found) . '</p>';

$found = $connection->search("K=БЕТОН$");
echo '<p>БЕТОН</p>';
echo '<p>' . implode(', ', $found) . '</p>';

$record = $connection->readRecord(123);
echo '<p>' .  $record->mfn . ' => ' . count($record->fields)  . '</p>';

$terms = $connection->readTerms("K=");
echo '<p>' . implode('<br/>', $terms) . '</p>';

$content = $connection->readTextFile("3.IBIS.WS.OPT");
echo '<p>' . $content . '</p>';

$connection->disconnect();

echo '<p>ALL DONE</p>';
