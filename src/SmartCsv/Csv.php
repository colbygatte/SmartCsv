<?php

namespace ColbyGatte\SmartCsv;

use ColbyGatte\SmartCsv\Coders\CoderInterface;
use ColbyGatte\SmartCsv\Traits\CsvIterator;
use Exception;
use Iterator;

class Csv implements Iterator
{
    use CsvIterator;

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
     * Filters for modifying data
     */
    private $filters = array();

    /**
     * Save rows when reading?
     *
     * An instance can only be set to save before reading is done.
     * Each instance only allows iterating once.
     *
     * @var bool
     */
    private $saveRows = true;

    /**
     * They key is the column to code.
     * The values are the coder to run it through.
     * Each column can only have one coder.
     */
    private $coders = array();

    /**
     * Ran before writing to a CSV file.
     */
    private function setUp()
    {
        if (($this->fileHandle = fopen($this->csvFile, 'r')) === false) {
            throw new Exception("Could not open {$this->csvFile}.");
        }

        $this->columnNamesAsValue = fgetcsv($this->fileHandle);
        $this->columnNamesAsKey = array_flip($this->columnNamesAsValue);
        $this->findIndexes();

        // If we are in alter mode, output the header
        if ($this->alter) {
            fputcsv($this->alter, $this->getHeader());
        }

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
    public function addFilter($callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * If $returnNewSet is true, the current instance will not be modified.
     */
    public function runFilters($returnNewSet = false)
    {
        foreach ($this as $row) {
            foreach ($this->filters as $filter) {
                $filter($row);
            }
        }

        return $this;
    }

    /**
     * @param \ColbyGatte\SmartCsv\Search $search
     *
     * @return \ColbyGatte\SmartCsv\Csv
     */
    public function runSearch(Search $search)
    {
        $results = csv([$this->getHeader()]);

        foreach ($this as $row) {
            if($search->runFilters($row)) {
                $results->appendRow($row);
            }
        }

        return $results;
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
    public function read($options)
    {
        if ($this->read) {
            throw new Exception('File already read!');
        }

        $this->parseOptions($options)
            ->setUp();

        // If we aren't saving the rows, they can only be accessed through each() or a foreach loop.
        if (! $this->saveRows) {
            // Read the first line
            if (($data = fgetcsv($this->fileHandle)) !== false) {
                $this->currentRow = new Row($this, $data);
            }

            return $this;
        }

        while (($data = fgetcsv($this->fileHandle)) !== false) {
            $row = new Row($this, $data);

            array_push($this->rows, $row);
        }

        $this->tearDown();

        return $this;
    }

    public function getHeader()
    {
        return $this->useAliases ? $this->convertAliases() : $this->columnNamesAsValue;
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

        fputcsv($fh, $this->getHeader());

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
     * @param $options
     *
     * @return $this
     * @throws \Exception
     */
    public function parseOptions($options)
    {
        if (is_string($options)) {
            $this->csvFile = $options;

            return $this;
        }

        // check for mandatory data

        if (! is_array($options)) {
            throw new Exception('Csv needs a string or an array.');
        }

        if (! isset($options['file'])) {
            throw new Exception('No file set to read CSV data from.');
        }

        $this->csvFile = $options['file'];

        // now check for options

        if (isset($options['alter'])) {
            $options['save'] = false; // The next if statement will take care of self::$saveRows

            $this->alter = fopen($options['alter'], 'w');
        }

        if (isset($options['save']) && is_bool($options['save'])) {
            $this->saveRows = $options['save'];
        }

        return $this;
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
     * Find rows where $column is equal to $value.
     *
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

            // If we are in alter mode, deleting the row will mean not saving it to the new CSV file,
            // so we just set the value of currentRow to false.
            if ($this->alter) {
                $this->currentRow = false;

                return true;
            }

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
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * @return $this
     */
    public function useAliases()
    {
        $this->useAliases = true;

        return $this;
    }
}
