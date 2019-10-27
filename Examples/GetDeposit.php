<head>
    <meta charset="UTF-8"/>
    <title>Чтение ресурса из папки Deposit</title>
</head>

<?php

require_once '../Source/PhpIrbis.php';

//
// С помощью небольшого хака можно напрямую
// прочитать ресурс из папки Deposit
// (независимо от наличия одноименного ресурса
// в папке базы данных).
//

try {
    $connection = new Irbis\Connection();
    $connection->host = '127.0.0.1';
    $connection->username = 'librarian';
    $connection->password = 'secret';
    $connection->database = 'IBIS';

    if (!$connection->connect()) {
        echo '<h3 style="color: red;">Не удалось подключиться!</h3>';
        echo '<p>', Irbis\describe_error($connection->lastError), '</p>';
        die(1);
    }

    $specification = '1..deposit/vdu.mnu';
    $resource = $connection->readTextFile($specification);
    echo '<pre>', $resource, '</pre>';

    $connection->disconnect();
}
catch (Exception $exception) {
    echo "ОШИБКА:  $exception";
}
