<?php

require_once ('../Source/PhpIrbis.php');

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
$connection->workstation = 'A';

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

$mfn = 123;
$format = "'Ἀριστοτέλης: ', v200^a";
$text = $connection->formatRecordUtf($format, $mfn);
echo '<p>Результат форматирования: ' . $text . '</p>';

$parameters = new SearchParameters();
$parameters->expression = '"A=ПУШКИН$"';
$parameters->format = BRIEF_FORMAT;
$parameters->numberOfRecords = 5;
$found = $connection->searchEx($parameters);
if (!$found) {
    echo 'Не нашли';
} else {
    $first = $found[0];
    echo "<p>MFN: {$first->mfn}, DESCRIPTION: {$first->description}</p>";
}

$single = $connection->searchSingleRecord('"I=65.304.13-772296"');
echo "<p>$single</p>";

$tree = $connection->readTreeFile('3.IBIS.II.TRE');
dumpArray($tree->roots);

$par = $connection->readParFile('1..IBIS.PAR');
echo "<p><pre>$par</pre></p>";

$opt = $connection->readOptFile('3.IBIS.WS31.OPT');
echo "<p><pre>$opt</pre></p>";
echo "<p>{$opt->resolveWorksheet('ASP')}</p>";

$connection->disconnect();

echo '<p>ALL DONE</p>';