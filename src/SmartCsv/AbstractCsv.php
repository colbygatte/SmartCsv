<?php

namespace ColbyGatte\SmartCsv;

use ColbyGatte\SmartCsv\Csv\Sip;
use ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper;
use Iterator;

/**
 * Base class for implementing CSV manipulation
 * Sub classes must implement @see \Iterator methods
 *
 * @package ColbyGatte\SmartCsv
 */
abstract class AbstractCsv implements Iterator
{
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
     * The CSV file handle.
     * $this->gets() and $this->puts() read from here if
     * no file handle is given.
     *
     * @var resource|bool
     */
    protected $fileHandle;
    
    /**
     * @var string
     */
    protected $delimiter = ',';
    
    /**
     * @var \ColbyGatte\SmartCsv\RowDataCoders
     */
    protected $coders;
    
    /**
     * Csv constructor.
     */
    public function __construct()
    {
        $this->columnGroupingHelper = new ColumnGroupingHelper($this);
    }
    
    /**
     * @param string $delimiter
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function setDelimiter($delimiter)
    {
        if ($this->read) {
            throw new Exception('Delimiter cannot be changed after reading/writing');
        }
        
        $this->delimiter = $delimiter;
        
        return $this;
    }
    
    /**
     * Used for only mode, where column header count won't be the same as the data count recieved.
     *
     * @return bool
     */
    public function getStrictMode()
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
    public function setHeader($header, $overwrite = false)
    {
        // If passed an instance of row, get integer-indexed array
        if ($header instanceof Row) {
            $header = $header->toArray(false);
        }
        
        if (! is_array($header)) {
            throw new Exception('Header must be an array.');
        }
        
        if ($this->columnNamesAsValue != null && ! $overwrite) {
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
        
        foreach ($this->columnGroups as $data) {
            $this->columnGroupingHelper->columnGroup(...$data);
        }
        
        return $this;
    }
    
    /**
     * @param null $useAliases
     *
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
        
        $this->writeRow($this->getHeader(), $fh);
        
        foreach ($this as $row) {
            $this->writeRow($row, $fh);
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
    abstract public function isReading();
    
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
            ->setHeader($columnIndexes, true)
            ->setStrictMode(false);
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
        } else {
            $this->columnGroupingHelper->columnGroup($name, $mandatoryColumn, $additionalColumns);
        }
        
        return $this;
    }
    
    /**
     * @param \ColbyGatte\SmartCsv\AbstractCsv $csvToSearch
     * @param array                            $parameters
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
     * @param array $aliases
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function setAliases($aliases)
    {
        $this->indexAliases = $aliases;
        
        // Check that no aliases are the same as current column names
        if ($duplicates = array_intersect(array_keys($aliases), $this->columnNamesAsValue)) {
            throw new Exception('Invalid alias name(s) (alias is an existing header name): '.implode(', ', $duplicates));
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
     * @param $column
     *
     * @return array
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function pluckFromRows($column)
    {
        if (! isset($this->columnNamesAsKey[$column])) {
            throw new Exception("Cannot pluck $column: column does not exist.");
        }
        
        $values = [];
        
        foreach ($this as $row) {
            $values[] = $row->$column;
        }
        
        return $values;
    }
    
    /**
     * @param callable $callback
     *
     * @return array
     */
    public function mapRows(callable $callback)
    {
        $new = [];
        
        foreach ($this as $row) {
            $new[] = $callback($row);
        }
        
        return $new;
    }
    
    /**
     * Iterate over each element.
     * $callable is passed the Row instance..
     *
     * NOTE: array_map() is not used because it would not work in sip mode
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this as $row) {
            $callback($row);
        }
        
        return $this;
    }
    
    /**
     * @return \ColbyGatte\SmartCsv\Row[]
     */
    public function getRows()
    {
        $rows = [];
        
        foreach ($this as $row) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Return the current element
     *
     * @return \ColbyGatte\SmartCsv\Row
     */
    abstract public function current();
    
    /**
     * Move forward to next element
     *
     * @return Row|null
     */
    abstract public function next();
    
    /**
     * Return the key of the current element
     *
     * @return int
     */
    abstract public function key();
    
    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    abstract public function valid();
    
    /**
     * Rewind the Iterator to the first element
     */
    abstract public function rewind();
    
    public function setCoders(RowDataCoders $coders)
    {
        $this->coders = $coders;
        
        return $this;
    }
    
    public function getCoders()
    {
        return $this->coders;
    }
    
    /**
     * @param array|Row $data
     * @param resource  $fh
     */
    protected function writeRow($data, $fh = null)
    {
        if ($data instanceof Row) {
            if ($this->coders) {
                $data = $this->coders->encodeData($data->toArray(true));
            } else {
                $data = $data->toArray(true);
            }
        }
        
        fputcsv(
            $fh ?: $this->fileHandle,
            array_values($data),
            $this->delimiter
        );
    }
    
    /**
     * @param bool $makeRow
     *
     * @return array|\ColbyGatte\SmartCsv\Row
     */
    protected function readRow($makeRow = true)
    {
        $data = fgetcsv($this->fileHandle, 0, $this->delimiter);
        
        if ($makeRow && $data !== false) {
            $row = new Row($this, $data);
            
            if ($this->coders) {
                $this->coders->applyDecoders($row);
            }
            
            return $row;
        }
        
        return $data;
    }
}
