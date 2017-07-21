<?php

namespace ColbyGatte\SmartCsv\Csv\Slurp;

use ColbyGatte\SmartCsv\Search;

class Alter extends Sip
{
    /**
     * @var resource
     */
    protected $alter;
    
    protected $alterSourceFile; // TODO: SET THIS!
    
    public function runSearch(Search $search)
    {
        throw new Exception('Cannot search in alter mode.');
    }
    
    public function delete($row, $reindex = true)
    {
        if ($row instanceof Row) {
            // In alter mode, deleting the row will mean not saving it to the new CSV file,
            // so we just set the value of currentRow to false.
            $this->currentRow = false;
            
            return true;
        }
    }
    
    public function next()
    {
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
    
    protected function setUp()
    {
        parent::setUp();
        
        $this->puts($this->getHeader(), $this->alter);
        
        $this->alter = fopen($this->alterSourceFile, 'w');
        
        return $this;
    }
}