<?php

namespace ColbyGatte\SmartCsv;

use Iterator;
use Countable;

class Row implements Iterator, Countable
{
    /**
     * @var \ColbyGatte\SmartCsv\Csv
     */
    protected $csv;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Row constructor.
     *
     * @param \ColbyGatte\SmartCsv\Csv $csv
     * @param array                    $data
     *
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function __construct(Csv $csv, array $data)
    {
        $dataCount = count($data);
        $columnCount = $csv->columnCount();

        if ($dataCount != $columnCount) {
            if ($csv->isStrictMode()) {
                $message = $csv->getFile() ? " (File: {$csv->getFile()})" : ' (no file set)';

                throw new Exception("Expected $columnCount data entry(s), received $dataCount.$message");
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
    protected function runDecoders()
    {
        /**
         * @var string                                     $column
         * @var \ColbyGatte\SmartCsv\Coders\CoderInterface $coder
         */
        foreach ($this->csv->getCoders() as $column => $coder) {
            $index = $this->csv->getIndex($column);

            if ($index === false) {
                continue;
            }

            $this->data[$index] = $coder::decode($this->data[$index]);
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
     * Pull only the given column data. Pulled data is not returned with associative keys.
     *
     * @param array $columns
     *
     * @return array
     */
    public function pluck($columns)
    {
        $data = [];

        foreach ($columns as $column) {
            $data[$column] = $this->get($column);
        }

        return $data;
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
    public function isEmpty($columns = null)
    {
        $columns = $columns ?: $this->csv->getHeader();

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
     * Set the value of a column using the column's header string.
     *
     * @param $indexString
     * @param $value
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
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
     * Set the value of a column using that columns zero-based index.
     *
     * @param $index
     * @param $value
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
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
     * For coders, we use a new instance of Row.
     *
     * @return array
     */
    public function toArray($associative = true)
    {
        $copy = $this->data;

        /**
         * @var string                                     $column
         * @var \ColbyGatte\SmartCsv\Coders\CoderInterface $coder
         */
        foreach ($this->csv->getCoders() as $column => $coder) {
            $index = $this->csv->getIndex($column);

            if ($index === false) {
                continue;
            }

            $copy[$index] = $coder::encode($this->data[$index]);
        }

        if ($associative) {
            $copy = array_combine($this->csv->getHeader(), $copy);
        }

        return $copy;
    }

    /**
     * @return string
     */
    public function toJson($options = 0, $depth = 512)
    {
        return json_encode($this->toArray(), $options, $depth);
    }

    /**
     * @return string
     */
    public function toCsv()
    {
        $fh = fopen('php://output', 'w');

        ob_start();

        fputcsv($fh, $this->toArray(false), $this->csv->getDelimiter());

        return ob_get_clean();
    }

    /**
     * @return \ColbyGatte\SmartCsv\Helper\RowGroupGetter
     */
    public function groups()
    {
        return $this->csv->columnGroupingHelper->setCurrentRow($this)
            ->getRowGroupGetter();
    }

    /**
     * @param $cached
     *
     * @return array
     */
    protected function groupSingleColumnsFromCache($cached)
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
    protected function groupMultipleColumnsFromCache($cached, $trimEnding)
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
     * @param $name
     *
     * @return false|mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toCsv();
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

    /**
     * Number of columns in the row.
     *
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Append an empty column onto the row. Should only be called from Csv::addColumn().
     *
     * @param string $value
     */
    public function addColumn($value = '')
    {
        array_push($this->data, $value);
    }
}
