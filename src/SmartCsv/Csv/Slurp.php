<?php

namespace ColbyGatte\SmartCsv\Csv\Slurp;

use ColbyGatte\SmartCsv\AbstractCsv;
use ColbyGatte\SmartCsv\Csv\Blank;

class Slurp extends Blank
{
    protected $csvSourceFile;
    
    public function setSourceFile($file) {
        $this->csvSourceFile = $file;
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
    
    /**
     * @return string|false
     */
    public function getFile()
    {
        return $this->csvSourceFile ?: false;
    }
}