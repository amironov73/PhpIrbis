<head>
    <meta charset="UTF-8"/>
    <title>Кириллические символы в логинах/паролях</title>
</head>

<?php

require_once '../Source/PhpIrbis.php';

//
// Пароли и логины пользователей могут содержать
// символы кириллицы (см. client_m.ini).
//

try {

    // Подключаемся к серверу
    $connection = new IrbisConnection();
    $connection->host = '127.0.0.1';
    $connection->username = 'БабаЯга';
    $connection->password = 'против!';
    $connection->database = 'IBIS';

    if (!$connection->connect()) {
        echo '<h3 style="color: red;">Не удалось подключиться!</h3>';
        echo '<p>', describe_error($connection->lastError), '</p>';
        die(1);
    }

    $found = $connection->searchCount('"A=ПУШКИН$"');
    echo '<h3>Успешное подключение</h3>';
    echo "<p>Логин: <strong>$connection->username</strong>, пароль: <strong>$connection->password</strong></p>";
    echo "<p>Всего найдено записей: <strong>$found</strong></p>";

    // Отключаемся от сервера
    $connection->disconnect();
}
catch (Exception $exception) {
    echo "ОШИБКА:  $exception";
}
