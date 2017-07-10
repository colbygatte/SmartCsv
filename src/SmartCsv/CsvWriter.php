<?php

namespace ColbyGatte\SmartCsv;

use ColbyGatte\SmartCsv\Traits\CsvIo;

/**
 * Class CsvWriter
 *
 * @package ColbyGatte\SmartCsv
 */
class CsvWriter
{
    use CsvIo;
    
    /**
     * @var bool
     */
    protected $didSetHeader = false;
    
    /**
     * @param $file
     *
     * @return $this
     * @throws \Exception
     */
    public function writeTo($file)
    {
        if (is_resource($file)) {
            $this->fileHandle = $file;
            
            return $this;
        }
        
        if (! touch($file)) {
            throw new \Exception("$file is not writable.");
        }
        
        $this->fileHandle = fopen($file, 'w');
        
        return $this;
    }
    
    /**
     * @param $header
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function setHeader($header)
    {
        if ($this->didSetHeader) {
            throw new Exception('Header has already been set.');
        }
        
        $this->didSetHeader = true;
        
        $this->puts($header);
        
        return $this;
    }
    
    /**
     * @param array ...$rows
     *
     * @return $this
     */
    public function append(...$rows)
    {
        array_map([$this, 'puts'], $rows);
        
        return $this;
    }
}