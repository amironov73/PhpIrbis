<head>
    <meta charset="UTF-8"/>
    <title>Простой пример поиска и расформатирования записей</title>
</head>

<?php

require_once '../Source/PhpIrbis.php';

try {

    // Подключаемся к серверу
    $connection = new IrbisConnection();
    $connectString = 'host=127.0.0.1;user=librarian;password=secret;';
    $connection->parseConnectionString($connectString);

    if (!$connection->connect()) {
        echo '<h3 style="color: red;">Не удалось подключиться!</h3>';
        echo '<p>', describeError($connection->lastError), '</p>';
        die(1);
    }

    // Из INI-файла можно получить настройки для клиента
    $ini = $connection->iniFile;
    echo "<p>Версия сервера: <b>{$connection->serverVersion}</b><br/>";
    echo "Интервал: <b>{$connection->interval}</b> мин.<br/>";
    $dbnnamecat = $ini->getValue('Main', 'DBNNAMECAT');
    echo "DBNAMECAT: <b>{$dbnnamecat}</b></p>\n";

    // Получаем список доступных баз данных
    $databases = $connection->listDatabases('1..' . $dbnnamecat);
    echo "<p>Имеются базы данных: <b>" . implode(', ', $databases) . "</b></p>\n";

    $found = $connection->search('"A=ПУШКИН$"');
    echo "<p>Всего найдено записей: " . count($found) . "</p>\n";

    if (count($found) > 10) {
        // Ограничиваемся первыми 10 записями
        $found = array_slice($found, 0, 10);
    }

    foreach ($found as $mfn) {
        // Считываем запись с сервера
        $record = $connection->readRecord($mfn);

        // Получаем значение поля/подполя
        $title = $record->fm(200, 'a');
        echo "<p><b>Заглавие:</b> {$title}<br/>";

        // Расформатируем запись на севере
        $description = $connection->formatRecord('@brief', $mfn);
        echo "<b>Биб. описание:</b> {$description}</p>\n";
    }

    // Отключаемся от сервера
    $connection->disconnect();
}
catch (Exception $exception) {
    echo "ОШИБКА:  $exception";
}
