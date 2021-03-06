<?php

namespace Smartling\DbAl;

/**
 * Interface SmartlingToCMSDatabaseAccessWrapperInterface
 *
 * @package Smartling\DbAl
 */
interface SmartlingToCMSDatabaseAccessWrapperInterface
{

    /**
     * the look of acs sort direction
     */
    const SORT_OPTION_ASC = 'ASC';

    /**
     * the look of desc sort direction
     */
    const SORT_OPTION_DESC = 'DESC';

    /**
     * Returns true if need raw SQL logging
     * @return bool
     */
    function needRawSqlLog();

    /**
     * Executes SQL query and returns the result
     *
     * @param $query
     *
     * @return mixed
     */
    function query($query);

    /**
     * Fetches data from database
     *
     * @param $query
     *
     * @return mixed
     */
    function fetch($query);

    /**
     * Escape string value
     *
     * @param string $string
     *
     * @return mixed
     */
    function escape($string);

    /**
     * @param string $tableName
     *
     * @return mixed
     */
    function completeTableName($tableName);

    /**
     * @return integer
     */
    function getLastInsertedId();

    /**
     * @return string
     */
    function getLastErrorMessage();
}