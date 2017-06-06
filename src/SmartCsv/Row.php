<?php

namespace ColbyGatte\SmartCsv;

use Iterator;

class Row implements Iterator
{
    /**
     * @var \ColbyGatte\SmartCsv\Csv
     */
    private $csv;

    private $data = [];

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

            $this->data[$index] = call_user_func([$coder, 'decode'], $this->data[$index]);
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

    private function groupMultipleColumnsFromCache($cached, $trimEnding)
    {
        $results = [];

        $searches = $cached['search'];

        foreach ($cached['groups'] as $group) {
            $ending = $group['ending'];

            $result = [];

            foreach ($searches as $key => $search) {
                $index = $group['indexes'][$key];

                $value = $this->data[$index];

                $result[$trimEnding ? $search : $search . $ending] = $value;
            }

            $results[] = $result;
        }

        return $results;
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