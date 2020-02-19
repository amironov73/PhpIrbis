<?php

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

require_once ('../Source/PhpIrbis.php');

function dumpArray($arr) {
    echo "<ol>";
    if ($arr) {
        foreach ($arr as $item) {
            echo '<li>', $item, '</li>';
        }
    }
    echo "</ol>", PHP_EOL;
}

$connection = new Irbis\Connection();
$connection->username = 'librarian';
$connection->password = 'secret';
$connection->workstation = 'A';

if (!$connection->connect()) {
    echo "Не удалось подключиться!", PHP_EOL;
    echo Irbis\describe_error($connection->lastError), PHP_EOL;
    die(1);
}

$ini = $connection->iniFile;
echo "<p>VERSION: {$connection->serverVersion}<br/>", PHP_EOL;
echo "INTERVAL: {$connection->interval}<br/>", PHP_EOL;
$dbnnamecat = $ini->getValue('Main', 'DBNNAMECAT');
echo "DBNNAMECAT: {$dbnnamecat}</p>", PHP_EOL;

$connection->noOp();
echo '<p>NO OP</p>', PHP_EOL;

$version = $connection->getServerVersion();
echo "<p>Версия: {$version->version} {$version->organization}</p>", PHP_EOL;

$processes = $connection->listProcesses();
dumpArray($processes);

$databases = $connection->listDatabases();
echo "<p>", PHP_EOL;
foreach ($databases as $db) {
    echo "{$db->name} {$db->description}<br/>", PHP_EOL;
}
echo "</p>", PHP_EOL;

$database = $connection->getDatabaseInfo();
echo "<p>LOGICALLY DELETED: ", implode(', ', $database->logicallyDeletedRecords), "</p>", PHP_EOL;

$users = $connection->getUserList();
echo "<p>", PHP_EOL;
dumpArray($users);
echo "</p>", PHP_EOL;

$stat = $connection->getServerStat();
echo "<p>", PHP_EOL;
dumpArray($stat->runningClients);
echo "</p>", PHP_EOL;

$maxMfn = $connection->getMaxMfn($connection->database);
echo "<p>MAX MFN: ", $maxMfn, "</p>", PHP_EOL;

$formatted = $connection->formatRecord("@brief", 123);
echo "<p>FORMATTED: ", $formatted, "</p>", PHP_EOL;

$formatted = $connection->formatRecords("@brief", array(1, 2, 3));
dumpArray($formatted);
echo PHP_EOL;

$files = $connection->listFiles("3.IBIS.brief.*");
echo '<p>', implode(', ', $files), '</p>', PHP_EOL;

$found = $connection->search("K=ALG$");
echo '<p>ALG$</p>', PHP_EOL;
echo '<p>', implode(', ', $found), '</p>', PHP_EOL;

$found = $connection->search("K=БЕТОН$");
echo '<p>БЕТОН</p>', PHP_EOL;
echo '<p>', implode(', ', $found), '</p>', PHP_EOL;

$found = $connection->searchAll("K=БЕТОН$");
echo '<p>БЕТОН ALL</p>', PHP_EOL;
echo '<p>', implode(', ', $found), '</p>', PHP_EOL;

$record = $connection->readRecord(123);
echo '<p>' , $record->mfn, ' => ', count($record->fields), '</p>';

$terms = $connection->readTerms("K=");
echo '<p>', implode('<br/>', $terms), '</p>';

$content = $connection->readTextFile("3.IBIS.WS.OPT");
echo '<p>', $content, '</p>';

$records = $connection->readRecords(array(10, 20, 30));
dumpArray($records);

$records = $connection->searchRead("K=ALG$", 10);
foreach ($records as $record) {
    echo $record->fm(200, 'a'), PHP_EOL;
}

$oneRecord = $connection->searchSingleRecord("K=ALG$");
echo $oneRecord->fm(200, 'a'), PHP_EOL;

$mfn = 123;
$format = "'Ἀριστοτέλης: ', v200^a";
$text = $connection->formatRecord($format, $mfn);
echo '<p>Результат форматирования: ', $text, '</p>', PHP_EOL;

$parameters = new Irbis\SearchParameters();
$parameters->expression = '"A=ПУШКИН$"';
$parameters->format = Irbis\BRIEF_FORMAT;
$parameters->numberOfRecords = 5;
$found = $connection->searchEx($parameters);
if (!$found) {
    echo '<p>Не нашли</p>', PHP_EOL;
} else {
    $first = $found[0];
    echo "<p>MFN: {$first->mfn}, DESCRIPTION: {$first->description}</p>", PHP_EOL;
}

$count = $connection->searchCount('"A=ПУШКИН$"');
echo "<p>COUNT: ", $count, "</p>", PHP_EOL;

$postings = $connection->getRecordPostings(2, "A=$");
dumpArray($postings);

try {
    $tree = $connection->readTreeFile('3.IBIS.II.TRE');
    dumpArray($tree->roots);
} catch (Irbis\IrbisException $e) {
    echo $e;
}

try {
    $par = $connection->readParFile('1..IBIS.PAR');
    echo "<p><pre>$par</pre></p>", PHP_EOL;
} catch (Irbis\IrbisException $e) {
    echo $e;
}

try {
    $opt = $connection->readOptFile('3.IBIS.WS31.OPT');
    echo "<p><pre>$opt</pre></p>", PHP_EOL;
    echo "<p>", $opt->resolveWorksheet('ASP'), "</p>", PHP_EOL;
} catch (Irbis\IrbisException $e) {
    echo $e;
}

$record = new Irbis\MarcRecord();
$field = $record->add(200);
$field->add('a', 'Заглавие')
    ->add('e', 'Подзаголовочные')
    ->add('f', 'Ответственность');
$format = 'v200^a, | : |v200^e, | / |v200^f';
$text = $connection->formatVirtualRecord($format, $record);
echo "<p>", $text, "</p>", PHP_EOL;

$languages = $connection->listTerms("J=");
dumpArray($languages);
echo PHP_EOL;

$connection->disconnect();

echo '<p>ALL DONE</p>', PHP_EOL;
