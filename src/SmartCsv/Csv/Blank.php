<?php

namespace ColbyGatte\SmartCsv\Csv;

use ColbyGatte\SmartCsv\AbstractCsv;
use ColbyGatte\SmartCsv\Exception;
use ColbyGatte\SmartCsv\Helper\ColumnGroupingHelper;
use ColbyGatte\SmartCsv\Row;
use ColbyGatte\SmartCsv\Search;

class Blank extends AbstractCsv
{
    public function next()
    {
        next($this->rows);
        
        return;
    }
    
    public function key()
    {
        return key($this->rows);
    }
    
    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->rows) !== null;
    }
    
    /**
     * @param $rowIndex
     *
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function get($rowIndex)
    {
        return isset($this->rows[$rowIndex])
            ? $this->rows[$rowIndex]
            : false;
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
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }
    
    /**
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
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
     * @param bool $reindex
     *
     * @return bool
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function delete($row, $reindex = true)
    {
        if ($row instanceof Row) {
            if (($index = array_search($row, $this->rows)) !== false) {
                unset($this->rows[$index]);
                
                return true;
            }
            
            return false;
        }
        
        if (! is_int($row)) {
            throw new Exception("Invalid row index $row.");
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
     * @param string $title
     * @param mixed $defaultValue Default value to assign to each new cell
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function addColumn($title, $defaultValue = '')
    {
        if (! is_string($title)) {
            throw new Exception("Title must be a string.");
        }
        
        $header = $this->getHeader(false);
        array_push($header, $title);
        
        $this->columnNamesAsValue = null;
        $this->columnNamesAsKey = null;
        
        $this->setHeader($header);
        
        foreach ($this as $row) {
            $row->addColumn($defaultValue);
        }
        
        $this->columnGroupingHelper = new ColumnGroupingHelper($this);
        
        return $this;
    }
    
    /**
     * Append row only if there the same instance isn't already present.
     *
     * @param \ColbyGatte\SmartCsv\Row $row
     *
     * @return \ColbyGatte\SmartCsv\AbstractCsv
     */
    public function appendIfUnique(Row $row)
    {
        if (! in_array($row, $this->rows)) {
            $this->rows[] = $row;
        }
        
        return $this;
    }
}