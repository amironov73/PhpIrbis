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

$record = new MarcRecord();
$record->add(100, 'Field100/1');
$record->add(100, 'Field100/2');
$record->add(200)->add('a', 'SubA')->add('b', 'SubB');
$record->add(920, 'PAZK');
$connection->writeRecord($record);
echo "<p>{$record}</p>";

$connection->disconnect();

echo '<p>ALL DONE</p>';
