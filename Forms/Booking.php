<html>
<head>
    <meta charset="UTF-8">
    <title>Бронирование рабочих мест в библиотеке</title>
    <style>
        body, table, td, p {
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

        #bookingForm {
            font: normal 12px Arial;
            padding-top: 2mm;
        }

        .mainTable {
            margin: 0 auto;
            width: 750px;
        }

        .caption {
            font: normal 12px Arial;
            width: 120pt;
            white-space: nowrap;
            padding-right: 10pt;
        }

        .value {
            font: normal 12px Arial;
            width: 100%;
        }

        .message {
            font: bold 20px Arial;
            text-align: center;
            color: red;
            text-transform: uppercase;
        }

        #goButton {
            width: 100%;
        }

    </style>
</head>
<body>
<h1 class="title1">НАЦИОНАЛЬНЫЙ УНИВЕРСИТЕТ<br/>НАУЧНО-ТЕХНИЧЕСКАЯ БИБЛИОТЕКА</h1>
<h2 class="title2">Бронирование рабочих мест в библиотеке</h2>

<?php

# require_once '../Source/PhpIrbis.php';

$showTable = true;

$fio      = $_REQUEST['fio'];
$ticket   = $_REQUEST['ticket'];
$group    = $_REQUEST['group'];
$phone    = $_REQUEST['phone'];
$date     = $_REQUEST['date'];
$time     = $_REQUEST['time'];
$number   = $_REQUEST['number'];
$software = $_REQUEST['software'];

if ($fio || $ticket || $group || $phone
    || $date || $time || $number || $software) {

    $showTable = false;

    if (!$fio || !$ticket || !$group || !$phone
        || !$date || !$time || !$number || !$software) {

        $showTable = true;
        echo "<p class='message'>Должны быть заполнены все поля!</p>";
    }
}

if ($showTable) :
?>

<form action='Booking.php' method='post' accept-charset='UTF-8' name='bookingForm' id='bookingForm'>

    <table class='mainTable'>
        <tr>
            <td colspan="2">
                <p style="text-align: center">
                    На этой странице Вы можете забронировать компьютер в Зале курсового и дипломного проектирования.
                    Ваша заявка будет внимательно рассмотрена и отклонена, о чём будет сообщено по федеральному телевидению.
                    Вы можете подать апелляцию, и она также будет внимательно рассмотрена и отклонена, о чем
                    также сообщат по главным федеральным каналам.<br/>
                    <br/>
                    <strong>Все поля в данной форме обязательны для заполнения.</strong>
                    <br/>
                    <br/>
                    Удачи Вам!
                </p>
                <p>&nbsp;</p>
            </td>
        </tr>
        <tr>
            <td class="caption">Фамилия, имя, отчество</td>
            <td class="value">
                <input name="fio" class="value" type="text" autocomplete="off">
            </td>
        </tr>
        <tr>
            <td class="caption">Читательский билет</td>
            <td class="value">
                <input name="ticket" class="value" type="text" autocomplete="off">
            </td>
        </tr>
        <tr>
            <td class="caption">Учебная группа</td>
            <td class="value">
                <input name="group" class="value" type="text" autocomplete="off">
            </td>
        </tr>
        <tr>
            <td class="caption">Телефон для связи</td>
            <td class="value">
                <input name="phone" class="value" type="text" autocomplete="off">
            </td>
        </tr>
        <tr>
            <td class="caption">Дата бронирования</td>
            <td class="value">
                <input name="date" class="value" type="text" autocomplete="off">
            </td>
        </tr>
        <tr>
            <td class="caption">Время бронирования</td>
            <td class="value">
                <input name="time" class="value" type="text" autocomplete="off">
            </td>
        </tr>
        <tr>
            <td class="caption">Номер компьютера</td>
            <td class="value">
                <input name="number" class="value" type="text">
            </td>
        </tr>
        <tr>
            <td class="caption">Необходимое ПО</td>
            <td class="value">
                <input name="software" class="value" type="text">
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <p>&nbsp;</p>
                <input type='submit' name='goButton' id='goButton' value='Подать заявку'>
            </td>
        </tr>
    </table>
</form>

<?php
else:

    $mailMessage = "ФИО: $fio\r\n"
        . "Читательский: $ticket"
        . "Группа: $group\r\n"
        . "Телефон: $phone\r\n"
        . "Дата: $date\r\n"
        . "Время: $time\r\n"
        . "Компьютер: $number\r\n"
        . "Софт: $software\r\n"
        ;

    $send = mail('amironov73@gmail.com', 'Бронирование ЗКиДП', $mailMessage);

    if ($send):
?>

<p class="message">Ваша заявка принята нафиг!</p>

<?php
    else:
?>

<p class="message">Возникла ошибка при отсылке заявки!</p>

<?php

    endif;

endif;

?>
</body>
</html>
