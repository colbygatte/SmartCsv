<?php

namespace ColbyGatte\SmartCsv;

use Iterator;

class Row implements Iterator
{
    /**
     * @var \ColbyGatte\SmartCsv\Csv
     */
    private $csv;

    private $data = array();

    public function __construct(Csv $csv, array $data)
    {
        $this->csv = $csv;
        $this->data = $data;

        $this->runDecoders();
    }

    /**
     * Should ONLY be called from constructor.
     *
     * @return void
     */
    private function runDecoders()
    {
        foreach ($this->csv->getCoders() as $column => $coder) {
            $index = $this->csv->getIndex($column);

            if ($index === false) {
                continue;
            }

            $this->data[$index] = call_user_func(array($coder, 'decode'), $this->data[$index]);
        }
    }

    /**
     * @param $indexString
     *
     * @return false|mixed
     */
    public function getCell($indexString)
    {
        if (isset($this->csv->indexAliases[$indexString])) {
            $indexString = $this->csv->indexAliases[$indexString];
        }

        if (($index = $this->csv->getIndex($indexString)) === false) {
            return false;
        }

        return $this->data[$index];
    }

    public function getCellByIndex($index)
    {
        return $this->data[$index];
    }

    /**
     * @param $indexString
     * @param $value
     *
     * @return bool
     */
    public function setCell($indexString, $value)
    {
        if (isset($this->csv->indexAliases[$indexString])) {
            $indexString = $this->csv->indexAliases[$indexString];
        }

        if (($index = $this->csv->getIndex($indexString)) !== false) {
            $this->data[$index] = $value;

            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->csv->deleteRow($this);
    }

    /**
     * @param $cached
     * @param $discardEmptyValues
     * @param $trimEnding
     */
    private function groupSingleColumnsFromCache($cached, $discardEmptyValues)
    {
        $results = array();

        foreach ($cached as $index) {
            $value = $this->data[$index];

            if (empty($value) && $discardEmptyValues) {
                continue;
            }

            $results[] = $value;
        }

        return $results;
    }

    private function groupMultipleColumnsFromCache($cached, $discardEmptyValues, $trimEnding)
    {
        $results = array();

        $searches = $cached['search'];

        foreach ($cached['groups'] as $group) {
            $ending = $group['ending'];

            $result = array();

            foreach ($searches as $key => $search) {
                $index = $group['indexes'][$key];

                $value = $this->data[$index];

                if (empty($value) && $discardEmptyValues) {
                    continue;
                }

                $result[$trimEnding ? $search : $search . $ending] = $value;
            }

            $results[] = $result;
        }
        
        return $results;
    }

    /**
     * Match up values from multiple columns.
     * Must have exact naming.
     * If ony $mandatoryColumn is given, the results are all returned in an array.
     * If $additionalColumns is given, an array of key-value paired arrays is returned.
     *
     * @param string $mandatoryColumn
     * @param array  $additionalColumns
     *
     * @param bool   $discardEmptyValues
     * @param bool   $trimEnding
     *
     * @return array
     */
    public function groupColumns(
        $mandatoryColumn,
        $additionalColumns = array(),
        $discardEmptyValues = true,
        $trimEnding = true
    ) {
        // Check if we have the indexes cached
        $cached = $this->csv->getCachedGroupColumnsSearch($mandatoryColumn, $additionalColumns);

        if ($cached) {
            if (empty($additionalColumns)) {
                return $this->groupSingleColumnsFromCache($cached, $discardEmptyValues);
            }

            return $this->groupMultipleColumnsFromCache($cached, $discardEmptyValues, $trimEnding);
        }

        $searchKeyLength = strlen($mandatoryColumn);

        $results = array();

        $cache = array();

        // Here, we iterate over all the cells.
        foreach ($this as $columnName => $value) {
            if (substr($columnName, 0, $searchKeyLength) != $mandatoryColumn // Does this column match our search term?
                || ($discardEmptyValues && empty($value))
            ) {
                continue;
            }

            // We need the ending to find other matching search values with the same ending
            $ending = substr($columnName, $searchKeyLength);

            // If we aren't grabbing additionalColumns, $results will be an array of the results from
            // the mandatory column.
            if (empty($additionalColumns)) {
                $results[] = $value;

                $cache[] = $this->csv->getIndex($columnName);

                continue;
            }

            $cacheIndexes = array($this->csv->getIndex($columnName));

            $result = array($trimEnding ? $mandatoryColumn : $columnName => $value);

            foreach ($additionalColumns as $searchValue) {
                $fullSearchValue = $searchValue . $ending;

                if (($value = $this->getCell($fullSearchValue)) !== false) {
                    $result[$trimEnding ? $searchValue : $fullSearchValue] = $value;

                    $cacheIndexes[] = $this->csv->getIndex($fullSearchValue);
                }
            }

            $cache[] = array(
                'ending' => $ending, 'indexes' => $cacheIndexes
            );

            $results[] = $result;
        }

        $this->csv->cacheGroupColumnsSearch($mandatoryColumn, $additionalColumns, $cache);

        return $results;
    }

    /**
     * Extracts all data from cells ($indexes) unless they are empty.
     *
     * @deprecated use groupColumns() instead.
     *
     * @param int []
     *
     * @return array
     */
    public function getNonEmptyCells($indexes)
    {
        $data = array();

        foreach ($indexes as $index) {
            if (! empty($this->data[$index])) {
                $data[] = $this->data[$index];
            }
        }

        return $data;
    }

    /**
     * For coders, we use a new instance of Row.
     *
     * @return mixed
     */
    public function toArray()
    {
        $copy = $this->data;

        foreach ($this->csv->getCoders() as $column => $coder) {
            $index = $this->csv->getIndex($column);

            if ($index === false) {
                continue;
            }

            $copy[$index] = call_user_func(array($coder, 'encode'), $this->data[$index]);
        }

        return $copy;
    }

    /**
     * @param $name
     *
     * @return false|mixed
     */
    function __get($name)
    {
        return $this->getCell($name);
    }

    /**
     * @param $name
     * @param $value
     *
     * @return bool
     */
    function __set($name, $value)
    {
        return $this->setCell($name, $value);
    }

    /**
     * Return the current element
     * @return string|false
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        next($this->data);
    }

    /**
     * Return the key of the current element
     * @return string
     */
    public function key()
    {
        return $this->csv->getIndexString(key($this->data));
    }

    /**
     * Checks if current position is valid
     */
    public function valid()
    {
        return key($this->data) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        reset($this->data);
    }
}