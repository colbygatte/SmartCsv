<?php

namespace ColbyGatte\SmartCsv;

use ColbyGatte\SmartCsv\Traits\CsvGetsAndPuts;
use ColbyGatte\SmartCsv\Traits\CsvIterator;
use ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper;
use Iterator;
use Exception;

class Csv implements Iterator
{
    use CsvIterator, CsvGetsAndPuts;

    /**
     * @var bool
     */
    private $strictMode = true;

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
     * Key: index alias strings
     * Value: corresponding index of each row
     *
     * @var array
     */
    private $columnNamesAsKey = [];

    /**
     * Key: index of rows
     * Value: index alias strings
     *
     * @var array
     */
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
     * no file handle is given.
     *
     * @var resource|bool
     */
    private $fileHandle = false;

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

    /**
     * @var bool
     */
    private $optionsParsed = false;

    /**
     * The CSV delimiter.
     *
     * @var string
     */
    private $delimiter = ',';

    /**
     * @var \ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper
     */
    public $columnGroupingHelper;

    /**
     * A container holding column group data passed to $this->makeGroup()
     * until the header has been read.
     *
     * @var array
     */
    private $columnGroups = [];

    /**
     * @var array|false
     */
    private $exclude = false;

    /**
     * Holds the options originally passed to this instance.
     * @var bool
     */
    private $parsedOptions = false;

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
            $this->currentRow = $this->gets();

            return $this;
        }

        // Read EVERYTHING!
        while ($row = $this->gets()) {
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
     * @param \ColbyGatte\SmartCsv\Row $row
     */
    public function appendIfUnique(Row $row)
    {
        if (! in_array($row, $this->rows)) {
            $this->rows[] = $row;
        }

        return $this;
    }

    /**
     * Used for only & except modes, where column header count won't be the same as the data count recieved.
     */
    public function isStrictMode()
    {
        return $this->strictMode;
    }

    public function setStrictMode($mode)
    {
        $this->strictMode = $mode;

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

        if (! is_int($row)) {
            throw new \Exception("Invalid row index $row.");
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

        // If strict mode is turned off (which it is for $this->only() & $this->exclude()
        // and the header is already set, throw it away
        if ($this->columnNamesAsKey != null) {
            if ($this->isStrictMode()) {
                throw new \Exception("Headers were already set before reading started!");
            } else {
                $this->gets(false);

                return $this;
            }
        }

        $this->setHeader($this->gets(false));

        // If we are in alter mode, output the header
        if ($this->alter) {
            $this->puts($this->getHeader(), $this->alter);
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
        $results = csv()->setHeader($this->getHeader());

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
     * @return \string[]
     */
    public function getHeader()
    {
        return $this->useAliases ? $this->convertAliases() : $this->columnNamesAsValue;
    }

    /**
     * @param array $header
     *
     * @return $this
     */
    public function setHeader($header)
    {
        if ($this->columnNamesAsValue != null) {
            throw new Exception('Header can only be set once!');
        }

        $this->columnNamesAsKey = array_flip($header);
        $this->columnNamesAsValue = $header;

        // If $this->columnNamesAsKey & $this->columnNamesAsValue are different,
        // all the column titles were not unique.
        if (count($this->columnNamesAsValue) != count($this->columnNamesAsKey)) {
            throw new Exception('Column titles must be unique.');
        }

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

        $this->puts($this->getHeader(), $fh);

        foreach ($this as $row) {
            $this->puts($row, $fh);
        }

        fclose($fh);

        // TODO: If in sip mode, reset to beginning.
    }

    /**
     * @param $columns
     *
     * @return string[] The missing columns (empty array if none)
     */
    public function hasColumns($columns)
    {
        $missing = [];

        foreach ($columns as $column) {
            if (! isset($this->columnNamesAsKey[$column])) {
                $missing[] = $column;
            }
        }

        return $missing;
    }

    /**
     * @return bool
     */
    public function isReading()
    {
        // FIXME: if reading started but there were no rows, this will still return false!
        if ($this->currentRow !== false || count($this->rows)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $columns
     *
     * @return Csv New CSV instance
     */
    public function only(array $columns)
    {
        $columnIndexes = [];

        foreach ($columns as $column) {
            $columnIndexes[$this->columnNamesAsKey[$column]] = $column;
        }

        $options = [
            'save' => false,
            'file' => $this->getFile()
        ];

        return (new static)->parseOptions($options)
            ->setHeader($columnIndexes)
            ->setStrictMode(false)
            ->read();
    }

    /**
     * If file option is not set, the file will not be read.
     * If file option IS set, the file will automatically be read.
     *
     * @param $options
     *
     * @return $this
     * @throws \Exception
     */
    public function parseOptions($options)
    {
        $this->parsedOptions = $options;

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

    /**
     * More options.
     *
     * @param $options
     *
     * @return $this
     */
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

    /**
     * @param \ColbyGatte\SmartCsv\Csv $csvToSearch
     * @param array                    $parameters
     *
     * @return \ColbyGatte\SmartCsv\Csv
     */
    public function findMatches(Csv $csvToSearch, array $parameters)
    {
        /** @var \ColbyGatte\SmartCsv\Csv $resultCsv */
        $resultCsv = (new static)->setHeader($csvToSearch->getHeader());

        foreach ($this as $row) {
            foreach ($csvToSearch as $rowToSearch) {
                foreach ($parameters as $column => $columnToMatch) {
                    if ($row->$column == $rowToSearch->$columnToMatch) {
                        $resultCsv->appendIfUnique($rowToSearch);

                        break;
                    }
                }
            }
        }

        return $resultCsv;
    }

    /**
     * Resets the rows array and returns the first row.
     * Only works in slurp mode.
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
}
