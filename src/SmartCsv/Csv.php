<?php

namespace ColbyGatte\SmartCsv;

use ColbyGatte\SmartCsv\Coders\CoderInterface;
use ColbyGatte\SmartCsv\Filters\FilterInterface;
use Exception;
use Iterator;

class Csv implements Iterator
{
    /**
     * ['alias' => 'Original']
     * @var array
     */
    public $indexAliases = array();

    /**
     * Whether or not to use aliases when writing to a CSV (instead of using original values)
     * @var bool
     */
    public $useAliases = false;

    /**
     * Key: the key from index mappers
     * Value: corresponding index of each row
     *
     * @var array
     */
    private $columnNamesAsKey = array();
    private $columnNamesAsValue = array();

    /**
     * Whether or not the csv file has been read or written.
     * Reading can only be done before any other actions are done.
     *
     * @var bool
     */
    private $read = false;

    /**
     * The CSV file being read.
     */
    public $csvFile;

    /**
     * @var \ColbyGatte\SmartCsv\Row[]
     */
    private $rows = array();

    /**
     * The CSV file handle.
     *
     * @var resource|bool
     */
    private $fileHandle = false;

    /**
     * They key is the column to filter
     * The values are the filters to run it through
     * Currently, there is no order.
     */
    private $filters = array();

    /**
     * They key is the column to code.
     * The values are the coder to run it through.
     * Each column can only have one coder.
     */
    private $coders = array();

    /**
     * Ran before writing to a CSV file.
     */
    private function setUp($csvFile)
    {
        $this->csvFile = $csvFile;

        if (($this->fileHandle = fopen($this->csvFile, 'r')) === false) {
            throw new Exception("Could not open {$this->csvFile}.");
        }

        $this->columnNamesAsValue = fgetcsv($this->fileHandle);
        $this->columnNamesAsKey = array_flip($this->columnNamesAsValue);
        $this->findIndexes();

        return $this;
    }

    /**
     * Ran after writing to a CSV file.
     */
    private function tearDown()
    {
        fclose($this->fileHandle);

        $this->read = true;

        return $this;
    }

    /**
     * @param string $column
     * @param string $filter
     *
     * @return $this
     */
    public function addFilter($column, $filter)
    {
        if (! is_subclass_of($filter, FilterInterface::class)) {
            throw new Exception("$filter does not implement FilterInterface.");

            // TODO: Check if this is necessary after throwing an error:
            return $this;
        }

        if (! isset($this->filters[$column])) {
            $this->filters[$column] = array();
        }

        $this->filters[$column][$filter] = $filter;

        return $this;
    }

    /**
     * Called from Row::getCell
     *
     * @param string $column
     * @param array  $value
     *
     * @return mixed
     */
    public function runFilters($column, $value)
    {
        if (isset($this->filters[$column]) && $filters = $this->filters[$column]) {
            foreach ($filters as $filter) {
                $value = call_user_func(array($filter, 'filter'), $value);
            }
        }

        return $value;
    }

    /**
     * @param string $column
     * @param string $coder
     *
     * @return $this
     */
    public function addCoder($column, $coder)
    {
        if (! is_subclass_of($coder, CoderInterface::class)) {
            throw new Exception("$coder does not implement CoderInterface.");
        }

        $this->coders[$column] = $coder;

        return $this;
    }

    /**
     * Used by Row.
     *
     * @return array
     */
    public function getCoders()
    {
        return $this->coders;
    }

    /**
     * @param string|null $csvFile
     *
     * @return $this
     */
    public function read($csvFile)
    {
        if ($this->read) {
            throw new Exception('File already read!');
        }

        $this->setUp($csvFile);

        while (($data = fgetcsv($this->fileHandle)) !== false) {
            $row = new Row($this, $data);

            array_push($this->rows, $row);
        }

        $this->tearDown();

        return $this;
    }

    /**
     * Write current CSV to file.
     * Note: After any writing is done, read() cannot be called.
     *
     * @param $toFile
     */
    public function write($toFile)
    {
        // if we are trying to write, then do not allow reading
        $this->read = true;

        $fh = fopen($toFile, 'w');

        fputcsv($fh, $this->useAliases ? $this->convertAliases() : $this->columnNamesAsValue);

        foreach ($this->rows as $row) {
            fputcsv($fh, $row->toArray());
        }

        fclose($fh);
    }

    /**
     * Convert the header line of the CSV to use the defined
     * aliases instead of the original values.
     */
    public function convertAliases()
    {
        $aliasesFlipped = array_flip($this->indexAliases);

        $headerUsingAliases = array();

        foreach ($this->columnNamesAsValue as $column) {
            if (isset($aliasesFlipped[$column])) {
                $headerUsingAliases[] = $aliasesFlipped[$column];
            } else {
                $headerUsingAliases[] = $column;
            }
        }

        return $headerUsingAliases;
    }

    /**
     * Allows manipulation of a CSV line-by-line, and saves it to a new location.
     * Good for large CSV files.
     * If the callback returns false, the line won't be saved to the new location.
     *
     * @param string   $csvFile
     * @param string   $saveLocation
     * @param callable $callback
     */
    public static function iterate($csvFile, $saveLocation, callable $callback)
    {
        $csv = new static($csvFile);
        $csv->setUp($csvFile);

        $fh = fopen($saveLocation, 'w');

        fputcsv($fh, $csv->columnNamesAsValue);

        while (($data = fgetcsv($csv->fileHandle)) !== false) {
            if ($callback($row = new Row($csv, $data)) === false) {
                continue;
            }

            fputcsv($fh, $row->toArray());

            unset($row);
        }

        fclose($fh);

        $csv->tearDown();
    }

    /**
     * Say you have a CSV whose index is Category IDs, but
     * all other CSV's are category_ids. Use index mappers for this:
     *  [
     *      'category_ids' => 'Category IDs'
     *  ]
     *
     * @return $this
     */
    private function findIndexes()
    {
        foreach ($this->indexAliases as $indexName => $indexSearchTerm) {
            $index = array_search($indexSearchTerm, $this->columnNamesAsValue);

            if ($index !== false) {
                $this->columnNamesAsKey[$indexName] = $index;
            }
        }

        $this->columnNamesAsValue = array_flip($this->columnNamesAsKey);

        return $this;
    }

    /**
     * @param $column
     * @param $value
     *
     * @return \ColbyGatte\SmartCsv\Row[]
     */
    public function findRows($column, $value)
    {
        return array_filter($this->rows, function ($row) use ($column, $value) {
            return $row->$column == $value;
        });
    }

    public function setHeader(array $header)
    {
        $this->columnNamesAsKey = array_flip($header);

        $this->columnNamesAsValue = $header;
    }

    /**
     * Perform a regex search for keys matching $regex.
     *
     * @return array Array of the index numbers that match
     */
    public function regexIndexStringSearch($regex)
    {
        return array_keys(array_filter($this->columnNamesAsValue, function ($value) use ($regex) {
            return preg_match($regex, $value) ? true : false;
        }));
    }

    /**
     * Get the index based on the CSV header
     */
    public function getIndex($indexString)
    {
        $index = isset($this->columnNamesAsKey[$indexString]) ? $this->columnNamesAsKey[$indexString] : false;

        return $index;
    }

    public function getIndexString($index)
    {
        $indexString = isset($this->columnNamesAsValue[$index]) ? $this->columnNamesAsValue[$index] : false;

        return $indexString;
    }

    public function first()
    {
        return reset($this->rows);
    }

    /**
     * @param $rowIndex
     *
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function getRow($rowIndex)
    {
        return $this->rows[$rowIndex];
    }

    /**
     * @param Row|array $data
     *
     * @return bool
     */
    public function appendRow($data = array())
    {
        if ($data instanceof Row) {
            $this->rows[] = $data;

            return true;
        }

        $this->rows[] = new Row($this, $data);

        return true;
    }

    public function deleteRow($row, $reindex = true)
    {
        if ($row instanceof Row) {
            if (($index = array_search($row, $this->rows)) !== false) {
                unset($this->rows[$index]);

                return true;
            }

            return false;
        }

        if (isset($this->rows[$row])) {
            unset($this->rows[$row]);

            if ($reindex) {
                $this->rows = array_values($this->rows);
            }

            return true;
        }

        return false;
    }

    /**
     * Iterate over each element.
     * $callable is passed the Row instance..
     *
     * @param callable $callback
     */
    public function each(callable $callback)
    {
        foreach ($this as $row) {
            $callback($row);
        }
    }

    /**
     * @return $this
     */
    public function useAliases()
    {
        $this->useAliases = true;

        return $this;
    }

    /**
     * Return the current element
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function current()
    {
        return current($this->rows);
    }

    /**
     * Move forward to next element
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function next()
    {
        return next($this->rows);
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        return key($this->rows);
    }

    /**
     * Checks if current position is valid
     * @return bool
     */
    public function valid()
    {
        return key($this->rows) !== null;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        reset($this->rows);
    }
}
