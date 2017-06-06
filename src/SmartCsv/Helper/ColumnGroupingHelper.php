<?php

namespace ColbyGatte\SmartCsv\Helper;

use ColbyGatte\SmartCsv\Csv;
use ColbyGatte\SmartCsv\Row;

class ColumnGroupingHelper
{
    private $cachedIndexGroups = [
        'single' => [],
        'multiple' => [],
        'info' => []
    ];
    
    private $columnNamesAsValue;

    /**
     * @var \ColbyGatte\SmartCsv\Csv
     */
    private $csv;

    /**
     * @var \ColbyGatte\SmartCsv\Row
     */
    private $currentRow;
    
    public function __construct(Csv $csv, $columnNamesAsValue = [])
    {
        $this->csv = $csv;
        $this->columnNamesAsValue = $columnNamesAsValue;
    }

    public function setColumnNames($columnNamesAsValue)
    {
        $this->columnNamesAsValue = $columnNamesAsValue;
    }
    
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
                if (empty($value)) {
                    $cacheData[] = $this->csv->getIndex($columnName);

                    continue;
                }

                $cacheData[] = $this->csv->getIndex($columnName);

                continue;
            }

            // We need the ending to find other matching search values with the same ending
            $ending = substr($columnName, $searchKeyLength);

            $cacheIndexes = [$this->csv->getIndex($columnName)];

            foreach ($additionalColumns as $searchValue) {
                $fullSearchValue = $searchValue . $ending;

                if ($index = $this->csv->getIndex($fullSearchValue)) {
                    $cacheIndexes[] = $this->csv->getIndex($fullSearchValue);
                }
            }

            $cacheData[] = [
                'ending' => $ending, 'indexes' => $cacheIndexes
            ];
        }

        $info = [
            'name' => $name
        ];

        $this->cacheGroupColumnsSearch($mandatoryColumn, $additionalColumns, $cacheData, $info);
    }

    public function getColumnGroup($name)
    {
        if (! $info = isset($this->cachedIndexGroups['info'][$name]) ? $this->cachedIndexGroups['info'][$name] : false) {
            return false;
        }

        if ($info['type'] == 'multiple') {
            $info['cache'] = $this->cachedIndexGroups['multiple'][$info['id']];
        } else {
            $info['cache'] = $this->cachedIndexGroups['single'][$info['id']];
        }

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


    public function cacheGroupColumnsSearch($mandatoryColumn, $additionalColumns, $cache, $info)
    {
        if (empty($additionalColumns)) {
            $this->cachedIndexGroups['single'][$mandatoryColumn] = $cache;

            // add some info to info (lol)
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

    public function setCurrentRow(Row $row)
    {
        $this->currentRow = $row;

        return $this;
    }

    /**
     * A little magic.
     * Allows for $csv->first()->groups()->spec
     *
     * @param $name
     *
     * @return array
     */
    public function __get($name)
    {
        return $this->currentRow->group($name);
    }
}