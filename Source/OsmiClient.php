<?php

/** @noinspection PhpUnused */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Irbis;

/*
 * СЕРВИС OSMI CARDS
 *
 * https://osmicards.com
 *
 * Электронные карты лояльности. Это готовый набор ИТ-решений.
 * Это программный продукт, который связывает кассу или
 * ПО программы лояльности с непосредственными покупателями
 * через мобильные телефоны.
 */

/**
 * Клиент сервиса карт лояльности OSMI cards.
 * @package Irbis
 */
final class OsmiClient
{
    private $baseUrl;
    private $apiId;
    private $apiKey;

    /**
     * OsmiClient constructor.
     * @param string $baseUrl
     * @param string $apiId
     * @param string $apiKey
     */
    public function __construct($baseUrl, $apiId, $apiKey)
    {
        assert($baseUrl);
        assert($apiId);
        assert($apiKey);

        $this->baseUrl = $baseUrl;
        $this->apiId   = $apiId;
        $this->apiKey  = $apiKey;
    } // function __construct


    private function call_api($method, $url, $payload = false)
    {
        $curl = curl_init();

        switch ($method)
        {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($payload) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                }
                break;

            case 'PUT':
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;

            default:
                if ($payload) {
                    $url = sprintf('%s?%s', $url, http_build_query($payload));
                }
        }

        // Authentication:
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($curl, CURLOPT_USERPWD, "$this->apiId:$this->apiKey");

        curl_setopt($curl, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    } // function call_api

    /**
     * Создание новой карты.
     * @param string $cardNumber Номер карты.
     * @param string $templateName Имя используемого шаблона.
     * @return void
     */
    public function create_card($cardNumber, $templateName)
    {
        $this->call_api('POST', "/passes/$cardNumber/$templateName");
    } // function create_card

    /**
     * Создание нового шаблона.
     * @param string $templateName Имя создаваемого шаблона.
     * @param string $jsonText Текст шаблона.
     * @return void
     */
    public function create_template($templateName, $jsonText)
    {
        $this->call_api('POST', "/templates/$templateName", $jsonText);
    } // function create_template

    /**
     * Удаление карты с указанным номером.
     * @param string $cardNumber Номер карты
     * @param bool $push Послать уведомление?
     * @return void
     */
    public function delete_card($cardNumber, $push = false)
    {
        $url = "/passes/$cardNumber";
        if ($push) {
            $url .= '/push';
        }

        $this->call_api('DELETE', $url);
    } // function delete_card

    /**
     * Получение информации о карте с указанным номером.
     * @param string $cardNumber Номер карты.
     * @return mixed Информация о карте.
     */
    public function get_card_info($cardNumber)
    {
        $json = $this->call_api('GET', "/passes/$cardNumber");
        return json_decode($json, false);
    }

    /**
     * Получение списка карт.
     * @return array Массив карт.
     */
    public function get_card_list()
    {
        $json = $this->call_api('GET', '/passes');
        return json_decode($json, false)->cards;
    }

    /**
     * Запрос общих параметров сервиса.
     * @return mixed Полученные параметры.
     */
    public function get_defaults()
    {
        $json = $this->call_api('GET', '/defaults/all');
        return json_decode($json, false);
    }

    /**
     * Запрос списка доступных графических файлов.
     * @return array Полученный список.
     */
    public function get_images()
    {
        $json = $this->call_api('GET', '/images');
        return json_decode($json, false);
    }

    /**
     * Запрос общей статистики.
     * @return mixed Полученная статистика.
     */
    public function get_stat()
    {
        $json = $this->call_api('GET', '/stats/general');
        return json_decode($json, false);
    }

    /**
     * Запрос информации о шаблоне.
     * @param string $templateName Има шаблона
     * @return mixed Информация о шаблоне.
     */
    public function get_template($templateName)
    {
        $json = $this->call_api('GET', "/templates/$templateName");
        return json_decode($json, false);
    }

    /**
     * Запрос перечня доступных шаблонов.
     * @return array Массив доступных шаблонов.
     */
    public function get_template_list()
    {
        $json = $this->call_api('GET', '/templates/');
        return json_decode($json, false)->templates;
    }

    /**
     * Запрос информации о шаблоне.
     * @return mixed Информация о шаблоне.
     */
    public function ping()
    {
        $json = $this->call_api('GET', '/ping');
        return json_decode($json, false);
    }

    /**
     * Отправка ссылки на загрузку карты по email.
     * @param string $cardNumber Номер карты.
     * @param string $email Адрес электронной почты.
     * @return void
     */
    public function send_card_mail($cardNumber, $email)
    {
        $this->call_api('GET', "/passes/$cardNumber/mail/$email");
    }

    /**
     * Отправка ссылки на загрузку карты по SMS.
     * @param string $cardNumber Номер карты.
     * @param string $phoneNumber Номер телефона.
     * @return void
     */
    public function send_card_sms($cardNumber, $phoneNumber)
    {
        $this->call_api('GET', "/passes/$cardNumber/sms/$phoneNumber");
    }

    /**
     * Отправка пин-кода по SMS.
     * @param string $phoneNumber Номер телефона.
     * @return void
     */
    public function send_pin_code($phoneNumber)
    {
        $this->call_api('GET', "/activation/sendpin/$phoneNumber");
    }

    /**
     * Отправка пуш-уведомлений на указанные карты.
     * @param array $cardNumbers Номера карт.
     * @param string $messageText Текст уведомления.
     * @return void
     */
    public function send_push_message($cardNumbers, $messageText)
    {
        $obj = array('serials' => $cardNumbers, 'message' => $messageText);
        $this->call_api('GET', '/marketing/pushmessage', $obj);
    }

    /**
     * Смена шаблона для указанной карты.
     * @param string $cardNumber Номер карты.
     * @param string $templateName Имя нового шаблона.
     * @param bool $push Разослать пуш-уведомление.
     * @return void
     */
    public function set_card_template($cardNumber, $templateName, $push)
    {
        $url = "/passes/move/$cardNumber/$templateName";
        if ($push) {
            $url .= "/push";
        }

        $this->call_api('PUT', $url);
    }

    /**
     * Обновление значений для указанной карты.
     * @param string $cardNumber Номер карты.
     * @param string $jsonText Новые значения в формате JSON.
     * @param bool $push Разослать пуш-уведомление.
     * @return void
     */
    public function update_card($cardNumber, $jsonText, $push)
    {
        $url = "/passes/$cardNumber";
        if ($push) {
            $url .= "/push";
        }

        $this->call_api('PUT', $url, $jsonText);
    }

    /**
     * Обновление значений для указанного шаблона.
     * @param string $templateName Имя шаблона.
     * @param string $jsonText Новые значения в формате JSON.
     * @param bool $push Разослать пуш-уведомление.
     * @return void
     */
    public function update_template($templateName, $jsonText, $push)
    {
        $url = "/templates/$templateName";
        if ($push) {
            $url .= "/push";
        }

        $this->call_api('PUT', $url, $jsonText);
    }

    /**
     * Эта команда позволяет получить ранее сохраненные
     * регистрационные данные для карт, которые использовали
     * параметры полей из заданной группы. Данные возвращаются
     * только для карт со статусом <code>–registered–</code>.
     * @param string $groupName Имя группы.
     * @return array Массив регистрационных данных.
     */
    public function get_registrations($groupName)
    {
        $jsonText = $this->call_api('GET', "/registations/data/$groupName");
        return json_decode($jsonText, false)->registrations;
    }

    /**
     * Удаление регистрационных данных указанных карт.
     * @param array $cardNumbers Номера карт.
     * @return void
     */
    public function delete_registrations($cardNumbers)
    {
        $obj = array('registrations' => $cardNumbers);
        $this->call_api('POST', '/registration/deletedata', $obj);
    }


} // class OsmiClient
