<?php

error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

require_once ('../Source/HardFormat.php');

$connection = new Irbis\Connection();
$connection->username = 'librarian';
$connection->password = 'secret';
$connection->database = 'IBIS';
$connection->workstation = 'C';

if (!$connection->connect()) {
    echo "Не удалось подключиться!", PHP_EOL;
    echo Irbis\describe_error($connection->lastError), PHP_EOL;
    die(1);
}

$maxMfn = $connection->getMaxMfn($connection->database);
if ($maxMfn > 100) {
    $maxMfn = 100;
}

$formatter = new Irbis\HardFormat();

for ($mfn = 1; $mfn < $maxMfn; $mfn++) {
    $record = $connection->readRecord($mfn);
    if ($record) {
        $formatted = $formatter->brief($record);
        $worksheet = $formatter->get_worksheet();
        echo "<p><b>$mfn</b><br/>$worksheet<br/>$formatted</p>" . PHP_EOL;
    }
}

$connection->disconnect();

echo '<p>ALL DONE</p>', PHP_EOL;
