<?php

namespace ColbyGatte\SmartCsv;

use Iterator;
use Exception;

class Row implements Iterator
{
    /**
     * @var \ColbyGatte\SmartCsv\Csv
     */
    private $csv;

    private $data = [];

    public function __construct(Csv $csv, array $data)
    {
        $dataCount = count($data);
        $columnCount = $csv->columnCount();

        if ($dataCount != $columnCount) {
            if ($csv->isStrictMode()) {
                throw new exception("Expected $columnCount data entry(s), received $dataCount.");
            }

            $data = array_pad($data, $csv->columnCount(), '');
        }

        $this->csv = $csv;

        foreach (array_keys($csv->getHeader()) as $index) {
            $this->data[$index] = $data[$index];
        }

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

            $this->data[$index] = call_user_func([$coder, 'decode'], $this->data[$index]);
        }
    }

    /**
     * Use a string to get by header, integer to get by column ID.
     *
     * @param $indexString
     *
     * @return false|mixed
     */
    public function get($indexString)
    {
        if (is_int($indexString)) {
            return $this->getByIndex($indexString);
        }

        if (isset($this->csv->indexAliases[$indexString])) {
            $indexString = $this->csv->indexAliases[$indexString];
        }

        if (($index = $this->csv->getIndex($indexString)) === false) {
            return false;
        }

        return $this->data[$index];
    }

    /**
     * @param array $columns
     *
     * @return string[]
     */
    public function missingColumns($columns)
    {
        return $this->csv->missingColumns($columns);
    }

    /**
     * Check if columns are empty
     *
     * @param array $columns
     *
     * @return bool
     */
    public function isEmpty($columns = [])
    {
        if (empty($columns)) {
            $columns = $this->csv->getHeader();
        }

        foreach ($columns as $column) {
            if (! $this->$column) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $index
     *
     * @return mixed
     */
    public function getByIndex($index)
    {
        return $this->data[$index];
    }

    /**
     * @param $indexString
     * @param $value
     *
     * @return $this
     */
    public function set($indexString, $value)
    {
        if (is_int($indexString)) {
            return $this->setByIndex($indexString, $value);
        }

        if (isset($this->csv->indexAliases[$indexString])) {
            $indexString = $this->csv->indexAliases[$indexString];
        }

        if (($index = $this->csv->getIndex($indexString)) !== false) {
            $this->data[$index] = $value;

            return $this;
        }

        throw new Exception("Column $indexString not found.");
    }

    /**
     * @param $index
     *
     * @return $this
     */
    public function setByIndex($index, $value)
    {
        if (! isset($this->data[$index])) {
            throw new Exception("Column $index is out of range.");
        }

        $this->data[$index] = $value;
    }

    /**
     * @return void
     */
    public function delete()
    {
        $this->csv->delete($this);
    }

    /**
     * @param $name
     *
     * @return array|false
     */
    public function group($name, $trimEndings = true)
    {
        $data = $this->csv->columnGroupingHelper->getColumnGroup($name);

        if (! $data) {
            return false;
        }

        if ($data['type'] == 'single') {
            return $this->groupSingleColumnsFromCache($data['cache']);
        }

        return $this->groupMultipleColumnsFromCache($data['cache'], $trimEndings);
    }

    /**
     * @param $cached
     * @param $discardEmptyValues
     * @param $trimEnding
     */
    private function groupSingleColumnsFromCache($cached)
    {
        $results = [];

        foreach ($cached as $index) {
            $value = $this->data[$index];

            if (empty($value)) {
                continue;
            }

            $results[] = $value;
        }

        return $results;
    }

    /**
     * @param $cached
     * @param $trimEnding
     *
     * @return array
     */
    private function groupMultipleColumnsFromCache($cached, $trimEnding)
    {
        $results = [];

        foreach ($cached['groups'] as $group) {
            $ending = $group['ending'];

            $result = [];

            foreach ($cached['search'] as $key => $search) {
                $index = $group['indexes'][$key];

                $value = $this->data[$index];

                $result[$trimEnding ? $search : $search . $ending] = $value;
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * For coders, we use a new instance of Row.
     *
     * @return mixed
     */
    public function toArray($associative = false)
    {
        $copy = $this->data;

        foreach ($this->csv->getCoders() as $column => $coder) {
            $index = $this->csv->getIndex($column);

            if ($index === false) {
                continue;
            }
            $copy[$index] = call_user_func([$coder, 'encode'], $this->data[$index]);
        }

        if ($associative) {
            $copy = array_combine($this->csv->getHeader(), $copy);
        }

        return $copy;
    }

    public function groups()
    {
        return $this->csv->columnGroupingHelper->setCurrentRow($this);
    }

    /**
     * @param $name
     *
     * @return false|mixed
     */
    function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param $name
     * @param $value
     *
     * @return bool
     */
    function __set($name, $value)
    {
        return $this->set($name, $value);
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
     * Return the column title of the current element
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
