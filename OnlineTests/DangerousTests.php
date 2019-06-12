<?php

error_reporting(E_ALL);

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
$connection->username = 'librarian';
$connection->password = 'secret';
$connection->workstation = 'A';

if (!$connection->connect()) {
    echo "Не удалось подключиться!";
    die(1);
}

//$record = new MarcRecord();
//$record->add(100, 'Field100/1');
//$record->add(100, 'Field100/2');
//$record->add(200)->add('a', 'SubA')->add('b', 'SubB');
//$record->add(920, 'PAZK');
//$connection->writeRecord($record);
//echo "<p>$record</p>";

$statements = array (
    new GblStatement(ADD_FIELD, '3000', 'XXXXXXX', "'Hello'")
);

$settings = new GblSettings();
$settings->database = "IBIS";
$settings->mfnList = array(1, 2, 3);
$settings->statements = $statements;
$result = $connection->globalCorrection($settings);
dumpArray($result);

$connection->disconnect();

echo '<p>ALL DONE</p>';
