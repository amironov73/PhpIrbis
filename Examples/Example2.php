<head>
    <meta charset="UTF-8"/>
    <title>Простой пример формирования и сохранения записей</title>
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

    // Записи будут помещаться в базу SANDBOX
    $connection->database = 'SANDBOX';

    for ($i = 0; $i < 10; $i++) {
        // Создаем запись
        $record = new MarcRecord();

        // Наполняем ее полями: первый автор (поле с подолями),
        $record->add(700)
            ->add('a', 'Миронов')
            ->add('b', 'А. В.')
            ->add('g', 'Алексей Владимирович');

        // заглавие (поле с подполями),
        $record->add(200)
            ->add('a', "Работа ИРБИС64: версия {$i}.0")
            ->add('e', 'руководство пользователя');

        // выходные данные (поле с подполями),
        $record->add(210)
            ->add('a', 'Иркутск')
            ->add('c', 'ИРНИТУ')
            ->add('d', '2018');

        // рабочий лист (поле без подполей).
        $record->add(920, 'PAZK');

        // Отсылаем запись на сервер.
        // Обратно приходит запись,
        // обработанная AUTOIN.GBL.
        $connection->writeRecord($record);

        // Распечатываем обработанную запись
        echo '<p>' . $record->encode('<br/>') . '</p>';
    }

    // Отключаемся от сервера
    $connection->disconnect();
}
catch (Exception $exception) {
    echo "ОШИБКА:  $exception";
}
