<?php

namespace ColbyGatte\SmartCsv\Csv;

class Slurp extends Blank
{
    protected $csvSourceFile;
    
    /**
     * @param $file
     *
     * @return $this
     */
    public function setSourceFile($file)
    {
        $this->optionsParsed = true;
        
        $this->csvSourceFile = $file;
        
        return $this;
    }
    
    /**
     * Read all rows
     *
     * @return $this
     */
    function read()
    {
        parent::read();
        
        while ($row = $this->readRow()) {
            array_push($this->rows, $row);
        }
        
        fclose($this->fileHandle);
        
        $this->read = true;
        
        return $this;
    }
}