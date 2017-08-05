<?php

namespace ColbyGatte\SmartCsv\Csv;

use ColbyGatte\SmartCsv\AbstractCsv;

class Sip extends AbstractCsv
{
    protected $csvSourceFile;
    
    protected $currentRow;
    
    /**
     * @return $this
     */
    public function setSourceFile($file) {
        $this->csvSourceFile = $file;
    
        $this->optionsParsed = true;
        
        return $this;
    }
    
    public function read()
    {
        parent::read();
        
        $this->currentRow = $this->gets();
        
        return $this;
    }
    
    public function current()
    {
        return $this->currentRow;
    }
    
    /**
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function first()
    {
        return $this->current();
    }
    
    public function addColumn($title, $defaultValue = '')
    {
        throw new Exception("addColumn() can only be used in slurp mode.");
    }
    
    public function next()
    {
        if (! ($row = $this->gets())) {
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
        return $this->currentRow !== null;
    }
}