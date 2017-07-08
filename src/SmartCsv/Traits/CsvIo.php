<?php

namespace ColbyGatte\SmartCsv\Traits;

use ColbyGatte\SmartCsv\Row;

/**
 * CSV Input/Output
 *
 * @package ColbyGatte\SmartCsv\Traits
 */
trait CsvIo
{
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
     * @param array|Row $data
     * @param resource $fh
     */
    protected function puts($data, $fh = null)
    {
        fputcsv(
            $fh ?: $this->fileHandle,
            $data instanceof Row ? $data->toArray(false) : $data,
            $this->delimiter
        );
    }
    
    /**
     * @param bool $makeRow
     *
     * @return array|\ColbyGatte\SmartCsv\Row
     */
    protected function gets($makeRow = true)
    {
        $data = fgetcsv($this->fileHandle, 0, $this->delimiter);
        
        if ($makeRow && $data !== false) {
            return new Row($this, $data);
        }
        
        return $data;
    }
}