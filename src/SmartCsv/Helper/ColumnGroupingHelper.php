<?php

namespace ColbyGatte\SmartCsv\Helper;

use ColbyGatte\SmartCsv\AbstractCsv;
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
     * @var \ColbyGatte\SmartCsv\AbstractCsv
     */
    protected $csv;

    /**
     * @var \ColbyGatte\SmartCsv\Row
     */
    protected $currentRow;

    /**
     * ColumnGroupingHelper constructor.
     *
     * @param \ColbyGatte\SmartCsv\AbstractCsv $csv
     * @param array                            $columnNamesAsValue
     */
    public function __construct(AbstractCsv $csv, $columnNamesAsValue = [])
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

        empty($additionalColumns)
            ? $this->parseOnlyMandatoryColumn($name, $mandatoryColumn, $searchKeyLength)
            : $this->parseMandatoryAndAdditionalColumns($name, $mandatoryColumn, $additionalColumns, $searchKeyLength);
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

    protected function parseOnlyMandatoryColumn($name, $mandatoryColumn, $searchKeyLength)
    {
        $cacheData = [];

        foreach ($this->columnNamesAsValue as $columnName) {
            if (substr($columnName, 0, $searchKeyLength) != $mandatoryColumn) {
                continue;
            }

            $cacheData[] = $this->csv->getIndex($columnName);
        }

        $this->cachedIndexGroups['single'][$mandatoryColumn] = $cacheData;

        $this->cachedIndexGroups['info'][$name] = [
            'name' => $name,
            'type' => 'single',
            'id' => $mandatoryColumn
        ];
    }

    protected function parseMandatoryAndAdditionalColumns($name, $mandatoryColumn, $additionalColumns, $searchKeyLength)
    {
        $cacheData = [];

        foreach ($this->columnNamesAsValue as $columnName) {
            if (substr($columnName, 0, $searchKeyLength) != $mandatoryColumn) {
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

        $id = $this->cacheId($mandatoryColumn, $additionalColumns);

        array_unshift($additionalColumns, $mandatoryColumn);

        $this->cachedIndexGroups['multiple'][$id] = [
            'search' => $additionalColumns,
            'groups' => $cacheData
        ];

        $this->cachedIndexGroups['info'][$name] = [
            'type' => 'multiple',
            'name' => $name,
            'id' => $id
        ];
    }
}
