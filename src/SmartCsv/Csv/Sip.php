<?php

namespace ColbyGatte\SmartCsv\Csv;

use ColbyGatte\SmartCsv\AbstractCsv;
use ColbyGatte\SmartCsv\Exception;
use ColbyGatte\SmartCsv\RowDataCoders;

/**
 * Use Sip for reading a Csv, row by row (This is not necessarily line by line,
 * a single row of a CSV can have carriage returns)
 *
 * @package ColbyGatte\SmartCsv\Csv
 */
class Sip extends AbstractCsv
{
    /**
     * @var string File being read
     */
    protected $csvSourceFile;
    
    /**
     * @var \ColbyGatte\SmartCsv\Row
     */
    protected $currentRow;
    
    /**
     * Sets source file & reads first row
     *
     * @param string $file
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function setSourceFile($file)
    {
        if ($this->csvSourceFile) {
            throw new Exception('Source file already set.');
        }
        
        $this->csvSourceFile = $file;
        
        $this->fileHandle = fopen($file, 'r');
        
        $this->setHeader($this->readRow(false));
        
        return $this;
    }
    
    /**
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function current()
    {
        // This assumes that this function is only called after valid() has been called
        if ($this->currentRow === null) {
            $this->next();
        }
        
        return $this->currentRow;
    }
    
    public function next()
    {
        if (! ($row = $this->readRow())) {
            $this->currentRow = null;
            
            return;
        }
        
        $this->currentRow = $row;
        
        return $row;
    }
    
    public function key()
    {
        return 0;
    }
    
    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return ! feof($this->fileHandle);
    }
    
    /**
     * @return bool
     */
    public function isReading()
    {
        if ($this->currentRow !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        rewind($this->fileHandle);
        
        fgetcsv($this->fileHandle); // Re-reads the header
    }
}