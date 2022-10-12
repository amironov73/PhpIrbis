 <html lang="ru">

<head>
    <meta charset="UTF-8"/>
    <title>Поиск для чайников</title>
</head>

<?php

require_once __DIR__ . '/../Source/PhpIrbis.php';
require_once __DIR__ . '/../Source/Search.php';
require_once __DIR__ . '/../Source/Teapot.php';

//
// Данный пример демонстрирует "поиск для чайников".
//

?>

<body>

<?php

try {

    // Подключаемся к серверу
    $connection = new Irbis\Connection();
    $connectString = 'host=127.0.0.1;user=librarian;password=secret;';
    $connection->parseConnectionString($connectString);

    if (!$connection->connect()) {
        echo '<h3 style="color: red;">Не удалось подключиться!</h3>';
        echo '<p>', Irbis\describe_error($connection->lastError), '</p>';
        die(1);
    }

    $expression = 'лед и пламя'; // запрос на естественном языке

    $teapot = new Irbis\Teapot();
    $teapot->limit = 100; // ограничение на количество найденных записей
    $found = $teapot->search($connection, $expression);

    if (!$found) {
        echo '<h3 style="color: red">Ничего не найдено</h3>' . PHP_EOL;
    }
    else {
        $format = '@infow_h'; // используемый формат, пусть будет самый простой -- информационный
        $found = $connection->formatRecords($format, $found);

        echo '<h3 style="text-align: center">Найдены документы:</h3>' . PHP_EOL;
        echo '<ol>' . PHP_EOL;
        foreach ($found as $item) {
            echo '<li style="margin-top: 1em;">';
            echo strip_tags($item, '<br>');
            echo '</li>' . PHP_EOL;
        }

        echo '</ol>' . PHP_EOL;
    }

    // Отключаемся от сервера
    $connection->disconnect();

} catch (Exception $exception) {
    echo "ОШИБКА:  $exception";
}

?>

</body>
</html>
