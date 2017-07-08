<?php

namespace ColbyGatte\SmartCsv\Helper;

use ColbyGatte\SmartCsv\Csv;
use ColbyGatte\SmartCsv\Row;

class ColumnGroupingHelper
{
    /**
     * @var array
     */
    protected $cachedIndexGroups = [
        'single' => [],
        'multiple' => [],
        'info' => []
    ];
    
    /**
     * @var array
     */
    protected $columnNamesAsValue;
    
    /**
     * @var \ColbyGatte\SmartCsv\Csv
     */
    protected $csv;
    
    /**
     * @var \ColbyGatte\SmartCsv\Row
     */
    protected $currentRow;
    
    /**
     * ColumnGroupingHelper constructor.
     *
     * @param \ColbyGatte\SmartCsv\Csv $csv
     * @param array $columnNamesAsValue
     */
    public function __construct(Csv $csv, $columnNamesAsValue = [])
    {
        $this->csv = $csv;
        $this->columnNamesAsValue = $columnNamesAsValue;
    }
    
    /**
     * @return \ColbyGatte\SmartCsv\Helper\RowGroupGetter
     */
    public function getRowGroupGetter()
    {
        return new RowGroupGetter($this->currentRow);
    }
    
    /**
     * @param $columnNamesAsValue
     */
    public function setColumnNames($columnNamesAsValue)
    {
        $this->columnNamesAsValue = $columnNamesAsValue;
    }
    
    /**
     * @param       $name Name used to access the column group.
     * @param       $mandatoryColumn
     * @param array $additionalColumns
     */
    public function columnGroup($name, $mandatoryColumn, $additionalColumns = [])
    {
        $searchKeyLength = strlen($mandatoryColumn);
        
        $cacheData = [];
        
        // Here, we iterate over all the cells.
        foreach ($this->columnNamesAsValue as $columnName) {
            if (substr($columnName, 0, $searchKeyLength) != $mandatoryColumn) {
                continue;
            }
            
            if (empty($additionalColumns)) {
                $cacheData[] = $this->csv->getIndex($columnName);
                
                continue;
            }
            
            // We need the ending to find other matching search values with the same ending
            $ending = substr($columnName, $searchKeyLength);
            
            $cacheIndexes = [$this->csv->getIndex($columnName)];
            
            foreach ($additionalColumns as $searchValue) {
                $fullSearchValue = $searchValue.$ending;
                
                if ($index = $this->csv->getIndex($fullSearchValue)) {
                    $cacheIndexes[] = $this->csv->getIndex($fullSearchValue);
                }
            }
            
            $cacheData[] = [
                'ending' => $ending,
                'indexes' => $cacheIndexes
            ];
        }
        
        $info = [
            'name' => $name
        ];
        
        $this->cacheGroupColumnsSearch($mandatoryColumn, $additionalColumns, $cacheData, $info);
    }
    
    /**
     * @param string $mandatoryColumn
     * @param string[] $additionalColumns
     * @param          $cache
     * @param          $info
     *
     * @return void
     */
    public function cacheGroupColumnsSearch($mandatoryColumn, $additionalColumns, $cache, $info)
    {
        if (empty($additionalColumns)) {
            $this->cachedIndexGroups['single'][$mandatoryColumn] = $cache;
            
            $info['type'] = 'single';
            $info['id'] = $mandatoryColumn;
            
            $this->cachedIndexGroups['info'][$info['name']] = $info;
            
            return;
        }
        
        $id = $this->cacheId($mandatoryColumn, $additionalColumns);
        
        $info['type'] = 'multiple';
        $info['id'] = $id;
        
        array_unshift($additionalColumns, $mandatoryColumn);
        
        $this->cachedIndexGroups['multiple'][$id] = [
            'search' => $additionalColumns,
            'groups' => $cache
        ];
        
        $this->cachedIndexGroups['info'][$info['name']] = $info;
    }
    
    /**
     * Generate an ID for the search values.
     *
     * @param $mandatoryColumn
     * @param $additionalColumns
     *
     * @return string
     */
    public function cacheId($mandatoryColumn, $additionalColumns)
    {
        sort($additionalColumns);
        
        $additionalColumns[] = $mandatoryColumn;
        
        return serialize($additionalColumns);
    }
    
    /**
     * @param $name
     *
     * @return array
     */
    public function getColumnGroup($name)
    {
        if (! $info = isset($this->cachedIndexGroups['info'][$name]) ? $this->cachedIndexGroups['info'][$name] : false) {
            return false;
        }
        
        $info['cache'] = $this->cachedIndexGroups[$info['type']][$info['id']];
        
        return $info;
    }
    
    /**
     * @param $mandatoryColumn
     * @param $additionalColumns
     *
     * @return false|array
     */
    public function getCachedGroupColumnsSearch($mandatoryColumn, $additionalColumns)
    {
        if (empty($additionalColumns)) {
            if (isset($this->cachedIndexGroups['single'][$mandatoryColumn])) {
                return $this->cachedIndexGroups['single'][$mandatoryColumn];
            }
            
            return false;
        }
        
        $id = $this->cacheId($mandatoryColumn, $additionalColumns);
        
        if (isset($this->cachedIndexGroups['multiple'][$id])) {
            return $this->cachedIndexGroups['multiple'][$id];
        }
        
        return false;
    }
    
    /**
     * @return \ColbyGatte\SmartCsv\Row
     */
    public function getCurrentRow()
    {
        return $this->currentRow;
    }
    
    /**
     * @param \ColbyGatte\SmartCsv\Row $row
     *
     * @return $this
     */
    public function setCurrentRow(Row $row)
    {
        $this->currentRow = $row;
        
        return $this;
    }
}
