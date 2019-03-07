<head>
    <meta charset="utf-8">
</head>

<?php

require_once ('PhpIrbis.php');

$prefix = $_POST['prefix'];
$value = $_POST['value'];
$truncate = $_POST['truncate'];

if ($truncate) {
    $truncate = '$';
}

if (!$prefix || !$value) {

}

$expression = '"' . $prefix . $value . $truncate . '"';

$connection = new IrbisConnection();
$connection->username = '1';
$connection->password = '1';

if (!$connection->connect()) {
    echo 'Что-то пошло не так!';
    return;
}

try {
    $parameters = new SearchParameters();
    $parameters->database = $connection->database;
    $parameters->expression = $expression;
    $parameters->numberOfRecords = 1000;
    $parameters->format = "@brief";
    $found = $connection->searchEx($parameters);
    $records = FoundLine::toDescription($found);
    sort($records);
    echo "<ol>";
    foreach ($records as $item) {
        echo "<li>{$item}</li>";
    }
    echo "</ol>";
}
catch(Exception $exception) {
    echo 'Что-то пошло не так: ' . $exception;
}

$connection->disconnect();