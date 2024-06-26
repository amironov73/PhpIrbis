<html>
<head>
    <meta charset="UTF-8">
    <title>Бронирование рабочих мест в библиотеке</title>
    <script type="text/javascript">
        function checkCaptcha() {
            var response = grecaptcha.getResponse();
            // alert(response);
            return response.length != 0;
        }

        var onloadCallback = function() {
            grecaptcha.render('html_element', {
                'sitekey' : 'XXXX'
            });
        };
    </script>
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
<h1 class="title1">Иркутский государственный национальный исследовательский технический университет<br/>Научно-техническая библиотека</h1>
<h2 class="title2">Бронирование рабочих мест в библиотеке</h2>

<?php

# require_once '../Source/PhpIrbis.php';

$showTable = true;

$fio      = $_REQUEST['fio'];
$group    = $_REQUEST['group'];
$phone    = $_REQUEST['phone'];
$date     = $_REQUEST['date'];
$time     = $_REQUEST['time'];
$number   = $_REQUEST['number'];
$software = $_REQUEST['software'];

if ($fio || $group || $phone || $date
    || $time || $number || $software) {

    $showTable = false;

    if (!$fio || !$group || !$phone || !$date
        || !$time || !$number || !$software) {

        $showTable = true;
        echo "<p class='message'>Должны быть заполнены все поля!</p>";
    }
}

if ($showTable) :
    ?>

    <form action='Booking.php' method='post' accept-charset='UTF-8' name='bookingForm' id='bookingForm' onsubmit='return checkCaptcha();'>

        <table class='mainTable'>
            <tr>
                <td colspan="2">
                    <p style="text-align: center">
                        На этой странице Вы можете забронировать компьютер в Зале курсового и дипломного проектирования.<br/>
                        Ваша заявка будет внимательно рассмотрена и отклонена, о чём будет сообщено по федеральному телевидению.<br/>
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
                    <input name="fio" class="value" type="text"
                           value="<?=$fio?>"
                           autocomplete="off">
                </td>
            </tr>
            <tr>
                <td class="caption">Учебная группа</td>
                <td class="value">
                    <input name="group" class="value" type="text"
                           value="<?=$group?>"
                           autocomplete="off">
                </td>
            </tr>
            <tr>
                <td class="caption">Телефон для связи</td>
                <td class="value">
                    <input name="phone" class="value" type="text"
                           value="<?=phone?>"
                           autocomplete="off">
                </td>
            </tr>
            <tr>
                <td class="caption">Дата бронирования</td>
                <td class="value">
                    <input name="date" class="value" type="text"
                           value="<?=date?>"
                           autocomplete="off">
                </td>
            </tr>
            <tr>
                <td class="caption">Время бронирования</td>
                <td class="value">
                    <input name="time" class="value" type="text"
                           value="<?=time?>"
                           autocomplete="off">
                </td>
            </tr>
            <tr>
                <td class="caption">Номер компьютера</td>
                <td class="value">
                    <input name="number" class="value" type="text"
                           value="<?=number?>"
                           autocomplete="off">
                </td>
            </tr>
            <tr>
                <td class="caption">Необходимое ПО</td>
                <td class="value">
                    <input name="software" class="value" type="text"
                           value="<?=$software?>"
                           autocomplete="off">
                </td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <p>&nbsp;</p>
                    <div id="html_element"></div>
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
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit"
            async defer>
    </script>


<?php
else:

$mailMessage = "ФИО: $fio\r\n"
    . "Читательский: $ticket\r\n"
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

    <p class="message">Ваша заявка отослана оператору.<br/>Ожидайте ответа!</p>

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
