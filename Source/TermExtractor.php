<?php

error_reporting(E_ALL);

require_once "PhpIrbis.php";

/**
 * Search expression builder.
 */
final class TermExtractor
{
    /**
     * Connection.
     */
    private $_connection;

    /**
     * FST lines.
     */
    private $_lines;

    /**
     * TermExtractor constructor.
     * @param IrbisConnection $_connection
     */
    public function __construct(IrbisConnection $_connection)
    {
        $this->_connection = $_connection;

        // TODO read the FST
    } // function ExtractTerms

    /**
     * Extract terms from the record.
     * @param MarcRecord $record
     * @return array
     */
    public function ExtractTerms(MarcRecord $record)
    {
        $result = array();
        return $result;
    } // function ExtractTerms

} // class TermExtractor
