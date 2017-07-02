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
     * Do we want to save each previous row from the loop?
     * This only happens in no-save mode ($save is false).
     *
     * @var null|resource
     */
    protected $alter;

    /**
     * Return the current element
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function current()
    {
        // If we are in save mode, all data is loaded already.
        if ($this->saveRows) {
            return current($this->rows);
        }

        return $this->currentRow;
    }

    /**
     * Move forward to next element
     *
     * @return Row
     */
    public function next()
    {
        // If $this->saveRows is set, all rows have been loaded already.
        if ($this->saveRows) {
            next($this->rows);
            return;
        }

        // If we are in alter mode, write the previous line (only if it hasn't been unset, which means the row was deleted)
        if (is_resource($this->alter) && $this->currentRow) {
            $this->puts($this->currentRow, $this->alter);
        }

        if (! ($row = $this->gets())) {
            $this->currentRow = null;
            return;
        }

        $this->currentRow = $row;

        return $row;
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        if ($this->saveRows) {
            return key($this->rows);
        }

        return 0;
    }

    /**
     * Checks if current position is valid
     * @return bool
     */
    public function valid()
    {
        if ($this->saveRows) {
            return key($this->rows) !== null;
        }

        return $this->currentRow !== null;
    }

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
        foreach ($this as $row) {
            $callback($row);
        }

        return $this;
    }
}