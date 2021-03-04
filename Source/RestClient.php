<?php

/** @noinspection PhpUnused */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Irbis;

//
// Простой REST API клиент
//
// Нужен для OsmiClient
//


final class RestClient
{
    private $_handle = null; // curl resource handle
    private $_baseUrl = '';  // base URL

    /**
     * RestClient constructor.
     * @param string $_baseUrl
     */
    public function __construct($baseUrl)
    {
        assert($baseUrl);

        $this->_baseUrl = $baseUrl;
    } // function __construct

    public function execute($url, $method='GET', $parameters=[], $headers=[])
    {

    } // function execute

} // class RestClient
