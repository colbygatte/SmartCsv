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
     * Match up values from multiple columns.
     * Must have exact naming.
     *
     * @param string $mandatoryColumn
     * @param array  $additionalColumns
     *
     * @return array
     */
    public function groupColumns(
        $mandatoryColumn,
        array $additionalColumns,
        $discardEmptyKeys = true,
        $trimEnding = true
    ) {
        $searchKeyLength = strlen($mandatoryColumn);

        $results = array();

        foreach ($this as $columnName => $value) {
            if (substr($columnName, 0, $searchKeyLength) != $mandatoryColumn // Does this column match our search term?
                || ($discardEmptyKeys && empty($value))
            ) { // Do we want to skip if empty? Is this value empty?
                continue;
            }

            // We need the ending to find other matching search values with the same ending
            $ending = substr($columnName, $searchKeyLength);

            $result = array($trimEnding ? $mandatoryColumn : $columnName => $value);

            foreach ($additionalColumns as $searchValue) {
                $fullSearchValue = $searchValue . $ending;

                if (($value = $this->getCell($fullSearchValue)) !== false) {
                    $result[$trimEnding ? $searchValue : $fullSearchValue] = $value;
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Extracts all data from cells ($indexes) unless they are empty.
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