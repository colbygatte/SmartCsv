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
    private $currentRow = false;

    /**
     * Do we want to save each previous row from the loop?
     * This only happens in no-save mode ($save is false).
     *
     * @var null|resource
     */
    private $alter;

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

    /**
     * Return the current element
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function current()
    {
        if ($this->saveRows) {
            return current($this->rows);
        }

        $row = $this->currentRow;

        return $row;
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        if ($this->saveRows) {
            next($this->rows);

            return;
        }

        // If we are in alter mode, write the previous line (only if it hasn't been unset)
        if (is_resource($this->alter) && $this->currentRow) {
            fputcsv($this->alter, $this->currentRow->toArray());
        }

        if (($data = fgetcsv($this->fileHandle)) === false) {
            $this->currentRow = null;

            return;
        }

        $this->currentRow = new Row($this, $data);
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
        $this->rows;
    }
}