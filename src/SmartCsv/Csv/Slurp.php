<?php

namespace ColbyGatte\SmartCsv\Csv;

use ColbyGatte\SmartCsv\AbstractCsv;
use ColbyGatte\SmartCsv\Csv\Blank;

class Slurp extends Blank
{
    protected $csvSourceFile;
    
    public function setSourceFile($file) {
        $this->optionsParsed = true;
        
        $this->csvSourceFile = $file;
        
        return $this;
    }
    
    function read($options = null)
    {
        parent::read($options);
        
        // Read EVERYTHING!
        while ($row = $this->gets()) {
            array_push($this->rows, $row);
        }
        
        $this->tearDown();
        
        return $this;
    }
}