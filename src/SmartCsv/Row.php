<?php

namespace ColbyGatte\SmartCsv;

use Countable;

class Row implements Countable
{
    /**
     * @var \ColbyGatte\SmartCsv\AbstractCsv
     */
    protected $csv;
    
    /**
     * @var array
     */
    protected $data = [];
    
    /**
     * Row constructor.
     *
     * @param \ColbyGatte\SmartCsv\AbstractCsv $csv
     * @param array                            $data
     *
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function __construct(AbstractCsv $csv, array $data)
    {
        $dataCount = count($data);
        $columnCount = $csv->columnCount();
        
        if ($dataCount != $columnCount) {
            if ($csv->getStrictMode()) {
                $message = $csv->getFile() ? " (File: {$csv->getFile()})" : ' (no file set)';
                
                throw new Exception("Expected $columnCount data entry(s), received $dataCount.$message");
            }
            
            $data = array_pad($data, $csv->columnCount(), '');
        }
        
        $this->csv = $csv;
        
        foreach (array_keys($csv->getHeader()) as $index) {
            $this->data[$index] = $data[$index];
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
        
        if (is_array($indexString)) {
            return array_combine($indexString, array_map([$this, 'get'], $indexString));
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
     * @param $index
     *
     * @return mixed
     */
    public function getByIndex($index)
    {
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
     * @return void
     */
    public function delete()
    {
        if (method_exists($this->csv, 'delete')) {
            $this->csv->delete($this);
        }
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
     * @param int $options
     * @param int $depth
     *
     * @return string
     */
    public function toJson($options = 0, $depth = 512)
    {
        return json_encode($this->toArray(), $options, $depth);
    }
    
    /**
     * Returns data as-is (does not use encoder)
     *
     * @param bool $associative
     *
     * @return array
     */
    public function toArray($associative = true)
    {
        $copy = $this->data;
        
        if ($associative) {
            $copy = array_combine($this->csv->getHeader(), $copy);
        }
        
        return $copy;
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
     * @return string
     */
    public function __toString()
    {
        return $this->toCsv();
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
                
                $result[$trimEnding ? $search : ($search.$ending] = $value);
            }
            
            $results[] = $result;
        }
        
        return $results;
    }
}
