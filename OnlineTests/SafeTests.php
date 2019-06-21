<?php

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

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
echo "DBNNAMECAT: {$dbnnamecat}</p>";

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

$formatted = $connection->formatRecords("@brief", array(1, 2, 3));
dumpArray($formatted);

$files = $connection->listFiles("3.IBIS.brief.*");
echo '<p>' . implode(', ', $files) . '</p>';

$found = $connection->search("K=ALG$");
echo '<p>ALG$</p>';
echo '<p>' . implode(', ', $found) . '</p>' . PHP_EOL;

$found = $connection->search("K=БЕТОН$");
echo '<p>БЕТОН</p>';
echo '<p>' . implode(', ', $found) . '</p>' . PHP_EOL;

$found = $connection->searchAll("K=БЕТОН$");
echo '<p>БЕТОН ALL</p>';
echo '<p>' . implode(', ', $found) . '</p>' . PHP_EOL;

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
$text = $connection->formatRecord($format, $mfn);
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

$count = $connection->searchCount('"A=ПУШКИН$"');
echo "<p>COUNT: $count</p>";

//$single = $connection->searchSingleRecord('"I=65.304.13-772296"');
//echo "<p>$single</p>";

$postings = $connection->getRecordPostings(2, "A=$");
dumpArray($postings);

$tree = $connection->readTreeFile('3.IBIS.II.TRE');
dumpArray($tree->roots);

$par = $connection->readParFile('1..IBIS.PAR');
echo "<p><pre>$par</pre></p>";

$opt = $connection->readOptFile('3.IBIS.WS31.OPT');
echo "<p><pre>$opt</pre></p>";
echo "<p>{$opt->resolveWorksheet('ASP')}</p>";

$record = new MarcRecord();
$field = $record->add(200);
$field->add('a', 'Заглавие')
    ->add('e', 'Подзаголовочные')
    ->add('f', 'Ответственность');
$format = 'v200^a, | : |v200^e, | / |v200^f';
$text = $connection->formatVirtualRecord($format, $record);
echo "<p>$text</p>";

$languages = $connection->listTerms("J=");
dumpArray($languages);

$connection->disconnect();

echo '<p>ALL DONE</p>';
