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
 * Class OsmiClient Клиент сервиса карт лояльности OSMI cards.
 * @package Irbis
 */
final class OsmiClient
{
    private $_baseUrl = '';
    private $_apiId   = '';
    private $_apiKey  = '';

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

        $this->_baseUrl = $baseUrl;
        $this->_apiId   = $apiId;
        $this->_apiKey  = $apiKey;
    } // function __construct




} // class OsmiClient
