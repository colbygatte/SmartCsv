<?php

namespace ColbyGatte\SmartCsv;

/**
 * Class Coders
 *
 * @package ColbyGatte\SmartCsv
 */
class RowDataCoders
{
    /**
     * @var callable[]
     */
    protected $encoders;
    
    /**
     * @var callable[]
     */
    protected $decoders;
    
    /**
     * @param string   $column
     * @param callable $coder
     *
     * @return $this
     */
    public function setEncoder($column, callable $coder)
    {
        $this->encoders[$column] = $coder;
        
        return $this;
    }
    
    /**
     * @param string $column
     *
     * @return callable|bool
     */
    public function getEncoder($column)
    {
        return isset($this->encoders[$column]) ? $this->encoders[$column] : false;
    }
    
    /**
     * @param array $rowData
     *
     * @return array
     */
    public function encodeData($rowData)
    {
        foreach ($this->encoders as $column => $encoder) {
            if (! isset($rowData[$column])) {
                continue;
            }
        
            $rowData[$column] = $encoder($rowData[$column]);
        }
        
        return $rowData;
    }
    
    /**
     * @param          $column
     * @param callable $coder
     *
     * @return $this
     */
    public function setDecoder($column, callable $coder)
    {
        $this->decoders[$column] = $coder;
        
        return $this;
    }
    
    /**
     * @param $column
     *
     * @return bool
     */
    public function getDecoder($column)
    {
        return isset($this->decoders[$column]) ? $this->decoders[$column] : false;
    }
    
    /**
     * @param Row $row
     */
    public function applyDecoders(Row $row)
    {
        foreach ($this->decoders as $column => $decoder) {
            $row->$column = $decoder($row->$column);
        }
    }
}