<?php

namespace ColbyGatte\SmartCsv;

use ColbyGatte\SmartCsv\Coders\CoderInterface;
use ColbyGatte\SmartCsv\Csv\Blank;
use ColbyGatte\SmartCsv\Csv\Sip;
use ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper;
use ColbyGatte\SmartCsv\Traits\CsvIo;
use ColbyGatte\SmartCsv\Traits\CsvIterator;
use Iterator;

abstract class AbstractCsv implements Iterator
{
    use CsvIterator, CsvIo;
    
    /**
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
     * @var \ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper
     */
    public $columnGroupingHelper;
    
    /**
     * @var bool
     */
    protected $strictMode = true;
    
    /**
     * Key: index alias strings
     * Value: corresponding index of each row
     *
     * @var array
     */
    protected $columnNamesAsKey = [];
    
    /**
     * Key: index of rows
     * Value: index alias strings
     *
     * @var array
     */
    protected $columnNamesAsValue = [];
    
    /**
     * Whether or not the csv file has been read or written.
     * Reading can only be done before any other actions are done.
     *
     * @var bool
     */
    protected $read = false;
    
    /**
     * The CSV file being read.
     *
     * @var string
     */
    protected $csvSourceFile = null;
    
    /**
     * @var \ColbyGatte\SmartCsv\Row[]
     */
    protected $rows = [];
    
    /**
     * They key is the column to code.
     * The values are the coder to run it through.
     * Each column can only have one coder.
     *
     * @var array
     */
    protected $coders = [];
    
    /**
     * @var bool
     */
    protected $optionsParsed = false;
    
    /**
     * A container holding column group data passed to $this->makeGroup()
     * until the header has been read.
     *
     * @var array
     */
    protected $columnGroups = [];
    
    /**
     * Csv constructor.
     */
    public function __construct()
    {
        $this->columnGroupingHelper = new ColumnGroupingHelper($this);
    }
    
    /**
     * @param null $options
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function read()
    {
        if ($this->read) {
            throw new Exception('File already read!');
        }
        
        if (! $this->optionsParsed) {
            throw new Exception('No options have been set!');
        }
        
        $this->setUp();
    }
    
    /**
     * @param string $delimiter
     *
     * @return $this
     * @throws \Exception
     */
    public function setDelimiter($delimiter)
    {
        if ($this->read) {
            throw new \Exception('Delimiter cannot be changed after reading/writing');
        }
        
        $this->delimiter = $delimiter;
        
        return $this;
    }
    
    /**
     * This can be used in all modes because it is using the Iterator interface.
     *
     * @param \ColbyGatte\SmartCsv\Search $search
     *
     * @return \ColbyGatte\SmartCsv\Csv\Blank
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function runSearch(Search $search)
    {
        $results = (new Blank)->setHeader($this->getHeader());
        
        foreach ($this as $row) {
            if ($search->runFilters($row)) {
                $results->append($row);
            }
        }
        
        return $results;
    }
    
    /**
     * @param string $column
     * @param CoderInterface $coder
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function addCoder($column, $coder)
    {
        if (! in_array(CoderInterface::class, class_implements($coder))) {
            throw new Exception("$coder does not implement CoderInterface.");
        }
        
        $coder = is_string($coder) ? new $coder : $coder;
        
        $this->coders[$column] = $coder;
        
        return $this;
    }
    
    /**
     * Used for only mode, where column header count won't be the same as the data count recieved.
     *
     * @return bool
     */
    public function isStrictMode()
    {
        return $this->strictMode;
    }
    
    /**
     * @param bool $mode
     *
     * @return \ColbyGatte\SmartCsv\AbstractCsv
     */
    public function setStrictMode($mode)
    {
        $this->strictMode = ! ! $mode;
        
        return $this;
    }
    
    /**
     * @param array $header
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function setHeader($header)
    {
        if (! is_array($header)) {
            throw new Exception('Header must be an array.');
        }
        
        if ($this->columnNamesAsValue != null) {
            throw new Exception('Header can only be set once!');
        }
        
        $this->columnNamesAsKey = array_flip($header);
        $this->columnNamesAsValue = $header;
        
        // If $this->columnNamesAsKey & $this->columnNamesAsValue are different,
        // all the column titles were not unique. Lets tell throw an exception
        // showing which column titles were duplicates.
        if (count($this->columnNamesAsValue) != count($this->columnNamesAsKey)) {
            Utilities::throwElementNotUniqueException(
                $this->columnNamesAsValue,
                'Duplicate headers: %s'
            );
        }
        
        $this->columnGroupingHelper->setColumnNames($header);
        
        $this->setAliases();
        
        foreach ($this->columnGroups as $data) {
            $this->columnGroupingHelper->columnGroup(...$data);
        }
        
        return $this;
    }
    
    /**
     * @return string[]
     */
    public function getHeader($useAliases = null)
    {
        $useAliases = ($useAliases !== null) ? $useAliases : $this->useAliases();
        
        return $useAliases ? $this->convertAliases() : $this->columnNamesAsValue;
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
     * Used by Row.
     *
     * @return array
     */
    public function getCoders()
    {
        return $this->coders;
    }
    
    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
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
     * @param string|resource $toFile
     */
    public function write($toFile)
    {
        // If we are trying to write, then do not allow reading
        $this->read = true;
        
        $fh = is_resource($toFile) ? $toFile : fopen($toFile, 'w');
        
        $this->puts($this->getHeader(), $fh);
        
        foreach ($this as $row) {
            $this->puts($row, $fh);
        }
        
        fclose($fh);
    }
    
    /**
     * @param $columns
     *
     * @return string[] The missing columns (empty array if none)
     */
    public function missingColumns($columns)
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
     * @return string|false
     */
    public function getFile()
    {
        return $this->csvSourceFile ?: false;
    }
    
    /**
     * This must be called after the header has been parsed.
     * A new Csv instance will be returned, using sip mode.
     *
     * @param array $columns
     *
     * @return AbstractCsv New CSV instance, in sip mode.
     */
    public function only(array $columns)
    {
        $columnIndexes = [];
        
        foreach ($columns as $column) {
            $columnIndexes[$this->columnNamesAsKey[$column]] = $column;
        }
        
        return (new Sip)->setSourceFile($this->getFile())
            ->setHeader($columnIndexes)
            ->setStrictMode(false)
            ->read();
    }
    
    /**
     * @param string $name
     * @param string $mandatoryColumn
     * @param string[] $additionalColumns
     *
     * @return $this
     */
    public function makeGroup($name, $mandatoryColumn, $additionalColumns = [])
    {
        // If the header hasn't been set, groups can't be made. Save them until then.
        if (empty($this->columnNamesAsValue)) {
            $this->columnGroups[] = [$name, $mandatoryColumn, $additionalColumns];
        } else {
            $this->columnGroupingHelper->columnGroup($name, $mandatoryColumn, $additionalColumns);
        }
        
        return $this;
    }
    
    /**
     * @param \ColbyGatte\SmartCsv\AbstractCsv $csvToSearch
     * @param array $parameters
     *
     * @return \ColbyGatte\SmartCsv\AbstractCsv
     */
    public function findMatches(AbstractCsv $csvToSearch, array $parameters)
    {
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
    abstract public function first();
    
    /**
     * Get the index based on the CSV header
     *
     * @param string $indexString
     *
     * @return int|false
     */
    public function getIndex($indexString)
    {
        $index = isset($this->columnNamesAsKey[$indexString]) ? $this->columnNamesAsKey[$indexString] : false;
        
        return $index;
    }
    
    /**
     * @param $index
     *
     * @return string|false
     */
    public function getIndexString($index)
    {
        $indexString = isset($this->columnNamesAsValue[$index]) ? $this->columnNamesAsValue[$index] : false;
        
        return $indexString;
    }
    
    /**
     * Add index aliases to Csv::$columnNamesAsKey
     *
     * @return $this
     */
    public function setAliases($aliases = null)
    {
        if (! is_null($aliases)) {
            $this->indexAliases = $aliases;
        }
        
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
     * Ran before writing to a CSV file.
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    protected function setUp()
    {
        if (($this->fileHandle = fopen($this->csvSourceFile, 'r')) === false) {
            throw new Exception("Could not open {$this->csvSourceFile}.");
        }
        
        // If strict mode is turned off (which it is for $this->only()
        // and the header is already set, throw it away
        if ($this->columnNamesAsKey != null) {
            if ($this->isStrictMode()) {
                throw new Exception("Headers were already set before reading started!");
            } else {
                $this->gets(false);
                
                return $this;
            }
        }
        
        try {
            $this->setHeader($this->gets(false));
        } catch (\Exception $e) {
            throw new Exception("Error setting CSV header: {$e->getMessage()}");
        }
        
        return $this;
    }
}
