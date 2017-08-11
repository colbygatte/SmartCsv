<?php

namespace ColbyGatte\SmartCsv\Csv;

use ColbyGatte\SmartCsv\Exception;
use ColbyGatte\SmartCsv\Row;
use ColbyGatte\SmartCsv\Search;

class Alter extends Sip
{
    /**
     * @var resource
     */
    protected $alter;
    
    protected $alterSourceFile; // TODO: SET THIS!
    
    public function delete(Row $row, $reindex = true)
    {
        // In alter mode, deleting the row will mean not saving it to the new CSV file,
        // so we just set the value of currentRow to false.
        $this->currentRow = false;
        
        return true;
    }
    
    /**
     * @param $alterSourceFile
     *
     * @return \ColbyGatte\SmartCsv\Csv\Alter
     */
    public function setAlterSourceFile($alterSourceFile)
    {
        $this->alterSourceFile = $alterSourceFile;
    
        $this->alter = fopen($this->alterSourceFile, 'w');
    
        $this->writeRow($this->getHeader(), $this->alter);
    
        return $this;
    }
    
    public function next()
    {
        // If we are in alter mode, write the previous line (only if it hasn't been unset, which means the row was deleted)
        if (is_resource($this->alter) && $this->currentRow) {
            $this->writeRow($this->currentRow, $this->alter);
        }
        
        if (! ($row = $this->readRow())) {
            $this->currentRow = null;
            
            return;
        }
        
        $this->currentRow = $row;
        
        return $row;
    }
}