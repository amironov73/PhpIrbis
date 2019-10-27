<html>
<head>
    <meta charset="UTF-8">
    <style>
        body, table, td {
            font: normal 12px Arial;
        }

        .title1 {
            font: bold small-caps 18px Arial;
            text-align: center;
        }

        .title2 {
            font: bold small-caps 32px Arial;
            color: #003edf;
            text-align: center;
        }

        #searchForm {
            font: normal 12px Arial;
            padding-top: 2mm;
        }

        .searchTable {
            margin: 0 auto;
            width: 750px;
        }

        .navTable {
            margin: 0 auto;
            width: 750px;
        }

        .navTable a {
            font: bold 16px Arial;
            text-decoration: none;
        }

        .searchItem {
            font: normal 12px Arial;
            width: 100%;
        }

        #searchButton {
            width: 90%;
        }

        .inventory {
            font-weight: bold;
            color: #003edf;
            text-decoration: underline;
        }

        .ui-tooltip {
            background-color: #003edf;
            color: white;
            -webkit-box-shadow: none;
            box-shadow: none;
            border: none;
        }

        .ui-autocomplete {
            font: normal 12px Arial;
            position: absolute;
            top: 0;
            left: 0;
            cursor: default;
        }
    </style>
</head>
<body>
<h1 class="title1">НАЦИОНАЛЬНЫЙ УНИВЕРСИТЕТ<br/>НАУЧНО-ТЕХНИЧЕСКАЯ БИБЛИОТЕКА</h1>
<h2 class="title2">Электронный каталог</h2>
<?php

# error_reporting(E_ALL);

require_once '../Source/PhpIrbis.php';

try {
    $catalog = $_REQUEST['catalogBox'];
    $term1   = $_REQUEST['termBox1'];
    $value1  = $_REQUEST['valueBox1'];
    $trim1   = $_REQUEST['trimBox1'];
    $term2   = $_REQUEST['termBox2'];
    $value2  = $_REQUEST['valueBox2'];
    $trim2   = $_REQUEST['trimBox2'];
    $term3   = $_REQUEST['termBox3'];
    $value3  = $_REQUEST['valueBox3'];
    $trim3   = $_REQUEST['trimBox3'];
    $format  = $_REQUEST['formatBox'];

    // Подключаемся к серверу
    $connection = new Irbis\Connection();
    $connectString = 'host=127.0.0.1;user=librarian;password=secret;';
    $connection->parseConnectionString($connectString);

    if (!$connection->connect()) {
        echo "Не удалось подключиться!";
        echo Irbis\describe_error($connection->lastError) . PHP_EOL;
        die(1);
    }

    $ui = new Irbis\UI($connection);

    echo "<form action='Opac.php' method='post' accept-charset='UTF-8' name='searchForm' id='searchForm'>" . PHP_EOL;
    echo "<table class='searchTable'>" . PHP_EOL;

    // Выбор каталога
    echo "<tr>" . PHP_EOL;
    echo "<td>&nbsp;</td>" . PHP_EOL;
    echo "<td>Каталог:</td>" . PHP_EOL;
    echo "<td>";
    $ui->listDatabases('searchItem', $catalog);
    echo "</td>" . PHP_EOL;
    echo "<td>";
    echo "<a href='#' style='text-decoration: none;'>ИНСТРУКЦИЯ</a>" . PHP_EOL;
    echo "</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

    $scenarios = $ui->getSearchScenario();

    // Первая строка поиска
    echo "<tr>" . PHP_EOL;
    echo "<td>&nbsp;</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    $ui->listSearchScenario('termBox1', $scenarios, 'searchItem', 0, $term1);
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<input name='valueBox1' type='text' class='searchItem' autocomplete='off' value='$value1'>";
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<span class='searchItem'>";
    echo "<input name='trimBox1' type='checkBox' checked='checked'>";
    echo "<label for='trimBox1'>Усечение</label>";
    echo "</span>" . PHP_EOL;
    echo "</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

    // Вторая строка поиска
    echo "<tr>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<select name='andOr2'>";
    echo "<option value='*' selected>и</option>";
    echo "<option value='+'>или</option>";
    echo "</select>" . PHP_EOL;
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    $ui->listSearchScenario('termBox2', $scenarios, 'searchItem', 1, $term2);
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<input name='valueBox2' type='text' class='searchItem' autocomplete='off' value='$value2'>";
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<span class='searchItem'>";
    echo "<input name='trimBox2' type='checkBox' checked='checked'>";
    echo "<label for='trimBox2'>Усечение</label>";
    echo "</span>";
    echo "</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

    // Третья строка поиска
    echo "<tr>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<select name='andOr3'>";
    echo "<option value='*' selected>и</option>";
    echo "<option value='+'>или</option>";
    echo "</select>" . PHP_EOL;
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    $ui->listSearchScenario('termBox3', $scenarios, 'searchItem', 2, $term3);
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<input name='valueBox3' type='text' class='searchItem' autocomplete='off' value='$value3'>";
    echo "</td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<span class='searchItem'>";
    echo "<input name='trimBox3' type='checkBox' checked='checked'>";
    echo "<label for='trimBox3'>Усечение</label>";
    echo "</span>";
    echo "</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

    echo "<tr>" . PHP_EOL;
    echo "<td>&nbsp;</td>" . PHP_EOL;
    echo "<td>Формат выдачи: </td>" . PHP_EOL;
    echo "<td>" . PHP_EOL;
    echo "<select name='formatBox' class='searchItem'>" . PHP_EOL;
    echo "<option value='@brief'>Краткий</option>" . PHP_EOL;
    echo "<option value='@' selected>Полный</option>" . PHP_EOL;
    echo "<option value='@infow_h'>Информационный</option>" . PHP_EOL;
    echo "</select>" . PHP_EOL;
    echo "</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

    echo "<tr>" . PHP_EOL;
    echo "<td colspan='4'>" . PHP_EOL;
    echo "<input type='submit' name='searchButton' id='searchButton' value='Поиск'>";
    echo "</td>" . PHP_EOL;
    echo "</tr>" . PHP_EOL;

    echo "</table>" . PHP_EOL;
    echo "</form>" . PHP_EOL;

    echo "<div style='margin: 0 5%'>" . PHP_EOL;
    echo "<span id='resultBox'>" . PHP_EOL;

    $searchExpression = '';

    if ($value1) {
        $searchExpression = '"' . $term1 . $value1 . ($trim1 ? '$' : '') . '"';
    }

    if ($value2) {
        if ($searchExpression) {
            $searchExpression = $searchExpression . ' ' . $_REQUEST['andOr2'] . ' ';
        }
        $searchExpression = $searchExpression . '"' . $term2 . $value2
            . ($trim2 ? '$' : '') . '"';
    }

    if ($value3) {
        if ($searchExpression) {
            $searchExpression = $searchExpression . ' ' . $_REQUEST['andOr3'] . ' ';
        }
        $searchExpression = $searchExpression . '"' . $term3 . $value3
            . ($trim3 ? '$' : '') . '"';
    }

//    echo "<p>";
//    var_dump($_REQUEST);
//    echo "</p>";
//
//    echo "<p>";
//    var_dump($searchExpression);
//    echo "</p>";

    if ($searchExpression) {
        $connection->database = $catalog;
        $parameters = new Irbis\SearchParameters();
        $parameters->expression = $searchExpression;
        $parameters->numberOfRecords = 1000;
        $parameters->format = $format;
        $found = $connection->searchEx($parameters);
        $found = Irbis\FoundLine::toDescription($found);
        sort($found);

        echo "<p style='color: blue;text-align: center;'>Найдено записей: " . count($found) . "</p>";

        echo "<ol>";
        foreach ($found as $item) {
            echo "<li>$item</li>";
        }
        echo "</ol>";
    }

    echo "</span>" . PHP_EOL;
    echo "</div>" . PHP_EOL;

    $connection->disconnect();
}
catch (Exception $exception) {
    echo "ОШИБКА: " . $exception;
}

?>

</body>
</html>
