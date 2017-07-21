<?php

namespace ColbyGatte\SmartCsv\Traits;

use ColbyGatte\SmartCsv\Row;

/**
 * Trait CsvIterator
 *
 * Single-use trait extracted from ColbyGatte\SmartCsv\Csv.
 *
 * @package ColbyGatte\SmartCsv\Traits
 */
trait CsvIterator
{
    /**
     * Used for iteration.
     *
     * A starting value of false is used before reading, null is used after reading.
     *
     * @var null|false|\ColbyGatte\SmartCsv\Row
     */
    protected $currentRow = false;
    
    /**
     * Return the current element
     *
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function current()
    {
        return current($this->rows);
    }
    
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
    //{
    //    return $this->saveRows ? key($this->rows) !== null : $this->currentRow !== null;
    //}
    
    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        reset($this->rows);
    }
    
    /**
     * Iterate over each element.
     * $callable is passed the Row instance..
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        // array_map() is not used because it would not work in sip mode
        foreach ($this as $row) {
            $callback($row);
        }
        
        return $this;
    }
}