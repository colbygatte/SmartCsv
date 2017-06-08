<?php

namespace ColbyGatte\SmartCsv;

use ColbyGatte\SmartCsv\Traits\CsvIterator;
use ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper;
use Iterator;
use Exception;

class Csv implements Iterator
{
    use CsvIterator;

    /**
     * compatibility
     * ['alias' => 'Original']
     * @var array
     */
    public $indexAliases = [];

    /**
     * Whether or not to use aliases when writing to a CSV (instead of using original values)
     *
     * @var bool
     */
    public $useAliases = false;

    /**
     * Key: the key from index mappers
     * Value: corresponding index of each row
     *
     * @var array
     */
    private $columnNamesAsKey = [];
    private $columnNamesAsValue = [];

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
    private $csvFile;

    /**
     * @var \ColbyGatte\SmartCsv\Row[]
     */
    private $rows = [];

    /**
     * The CSV file handle.
     * $this->gets() and $this->puts() read from here if
     * no filehandle is given.
     *
     * @var resource|bool
     */
    private $fileHandle = false;

    /**
     * Filters for modifying data
     */
    private $filters = [];

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
    private $coders = [];

    private $optionsParsed = false;

    private $delimiter = ',';

    /**
     * @var \ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper
     */
    public $columnGroupingHelper;

    private $columnGroups = [];

    public function __construct()
    {
        $this->columnGroupingHelper = new ColumnGroupingHelper($this);
    }

    /**
     * @param string|null $csvFile
     *
     * @return $this
     */
    public function read($options = null)
    {
        if ($this->read) {
            throw new Exception('File already read!');
        }

        if ($options == null && ! $this->optionsParsed) {
            throw new Exception('No options have been set!');
        }

        if ($options) {
            $this->parseOptions($options);
        }

        $this->setUp();

        // If we aren't saving the rows, read the first line only.
        if (! $this->saveRows) {
            if (($data = $this->gets()) !== false) {
                $this->currentRow = new Row($this, $data);
            }

            return $this;
        }

        // Read EVERYTHING!
        while (($data = $this->gets()) !== false) {
            $row = new Row($this, $data);

            array_push($this->rows, $row);
        }

        $this->tearDown();

        return $this;
    }


    /**
     * @param Row\array
     *
     * @return $this
     */
    public function append()
    {
        if (empty($this->columnNamesAsValue)) {
            throw new Exception('Header must be set before adding rows!');
        }

        foreach (func_get_args() as $data) {
            if ($data instanceof Row) { // TODO: clone row in case it's coming from another CSV, check for equal amount of columns
                $this->rows[] = $data;
            } else {
                $this->rows[] = new Row($this, $data);
            }
        }

        return $this;
    }

    /**
     * @param \ColbyGatte\SmartCsv\Row|int $row
     * @param bool                         $reindex
     *
     * @return bool
     */
    public function delete($row, $reindex = true)
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
     * Ran before writing to a CSV file.
     *
     * @return $this
     */
    private function setUp()
    {
        if (($this->fileHandle = fopen($this->csvFile, 'r')) === false) {
            throw new Exception("Could not open {$this->csvFile}.");
        }

        $this->header($this->gets());

        // If we are in alter mode, output the header
        if ($this->alter) {
            $this->puts($this->header(), $this->alter);
        }

        return $this;
    }

    /**
     * Ran after writing to a CSV file.
     *
     * @return $this
     */
    private function tearDown()
    {
        fclose($this->fileHandle);

        $this->read = true;

        return $this;
    }

    /**
     * @param \ColbyGatte\SmartCsv\Search $search
     *
     * @return \ColbyGatte\SmartCsv\Csv
     */
    public function runSearch(Search $search)
    {
        $results = csv()->header($this->header());

        if ($this->alter) {
            throw new Exception('Cannot search in alter mode.');
        }

        foreach ($this as $row) {
            if ($search->runFilters($row)) {
                $results->append($row);
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
        if (! is_subclass_of($coder, 'ColbyGatte\SmartCsv\Coders\CoderInterface')) {
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
     * @return string|false
     */
    public function getFile()
    {
        return (! empty($this->csvFile)) ? $this->csvFile : false;
    }

    /**
     * @param array $header
     *
     * @return $this
     */
    public function header($header = null)
    {
        if ($header == null) {
            return $this->useAliases ? $this->convertAliases() : $this->columnNamesAsValue;
        }

        if ($this->columnNamesAsValue != null) {
            throw new Exception('Header can only be set once!');
        }

        $this->columnNamesAsKey = array_flip($header);
        $this->columnNamesAsValue = $header;
        $this->columnGroupingHelper->setColumnNames($header);
        $this->findIndexes();

        foreach ($this->columnGroups as $data) {
            call_user_func_array([$this->columnGroupingHelper, 'columnGroup'], $data);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function columnCount()
    {
        return count($this->columnNamesAsValue);
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

        $this->puts($this->header(), $fh);

        foreach ($this->rows as $row) {
            $this->puts($row, $fh);
        }

        fclose($fh);
    }

    /**
     * If file option is not set, the file will not be read.
     * If  file option IS set, the file will automatically be read.
     *
     * @param $options
     *
     * @return $this
     * @throws \Exception
     */
    public function parseOptions($options)
    {
        if (is_string($options)) {
            $this->csvFile = $options;

            $this->optionsParsed = true;

            return $this;
        }

        if (! is_array($options)) {
            throw new Exception('Csv needs a string or an array.');
        }

        $this->optionsParsed = true;

        foreach ($options as $option => $value) {
            switch ($option) {
                case 'file':
                    $this->csvFile = $value;
                    break;

                case 'alter':
                    $this->alter = fopen($value, 'w');
                    $this->saveRows = false;
                    break;

                case 'save':
                    if (is_bool($value)) {
                        $this->saveRows = $value;
                    }
                    break;
            }
        }

        $this->presets($options);

        return $this;
    }

    public function presets($options)
    {
        foreach ($options as $option => $value) {
            switch ($option) {
                case 'del':
                    $this->delimiter = $value;
                    break;

                case 'aliases':
                    $this->indexAliases = $value;
                    $this->findIndexes();
                    break;

                case 'coders':
                    foreach ($value as $column => $coder) {
                        call_user_func([$this, 'addCoder'], $column, $coder);
                    }
                    break;

                case 'column-groups':
                    call_user_func_array([$this, 'makeGroup'], $value);
                    break;
            }
        }

        return $this;
    }


    /**
     * Convert the header line of the CSV to use the defined
     * aliases instead of the original values.
     *
     * @return string[]
     */
    public function convertAliases()
    {
        $aliasesFlipped = array_flip($this->indexAliases);

        $headerUsingAliases = [];

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
     * @param string   $name
     * @param string   $mandatoryColumn
     * @param string[] $additionalColumns
     *
     * @return $this
     */
    public function makeGroup($name, $mandatoryColumn, $additionalColumns = [])
    {
        // If the header hasn't been set, groups can't be made. Save them until then.
        if (empty($this->columnNamesAsValue)) {
            $this->columnGroups[] = [$name, $mandatoryColumn, $additionalColumns];

            return $this;
        }

        $this->columnGroupingHelper->columnGroup($name, $mandatoryColumn, $additionalColumns);

        return $this;
    }

    public function findMatches(Csv $csvToSearch, array $parameters)
    {
        $resultCsv = csv()->header($csvToSearch->header());

        foreach ($this as $row) {
            foreach ($csvToSearch as $rowToSearch) {
                foreach ($parameters as $column => $columnToMatch) {
                    if ($row->$column == $rowToSearch->$columnToMatch) {
                        $resultCsv->append($rowToSearch);

                        break;
                    }
                }
            }
        }

        return $resultCsv;
    }

    /**
     * Resets the rows array and returns the first row.
     *
     * @return \ColbyGatte\SmartCsv\Row|null
     */
    public function first()
    {
        return reset($this->rows);
    }

    /**
     * @param $rowIndex
     *
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function get($rowIndex)
    {
        return $this->rows[$rowIndex];
    }
    // endregion

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
     * Get the index based on the CSV header
     *
     * @param string $indexString
     *
     * @return bool|int
     */
    public function getIndex($indexString)
    {
        $index = isset($this->columnNamesAsKey[$indexString]) ? $this->columnNamesAsKey[$indexString] : false;

        return $index;
    }

    /**
     * @param $index
     *
     * @return string|bool
     */
    public function getIndexString($index)
    {
        $indexString = isset($this->columnNamesAsValue[$index]) ? $this->columnNamesAsValue[$index] : false;

        return $indexString;
    }


    /**
     * @param      $data
     * @param bool $fh
     */
    private function puts($data, $fh = false)
    {
        if (! $fh) {
            $fh = $this->fileHandle;
        }

        if ($data instanceof Row) {
            $data = $data->toArray();
        }

        fputcsv($fh, $data, $this->delimiter);
    }

    /**
     * @param resource $fh
     *
     * @return array
     */
    private function gets($fh = false)
    {
        if (! $fh) {
            $fh = $this->fileHandle;
        }

        return fgetcsv($fh, 0, $this->delimiter);
    }
}
