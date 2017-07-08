<?php

namespace ColbyGatte\SmartCsv\Helper;

use ColbyGatte\SmartCsv\Row;

/**
 * @package ColbyGatte\SmartCsv\Helper
 */
class RowGroupGetter
{
    /**
     * @var \ColbyGatte\SmartCsv\Row
     */
    protected $row;
    
    /**
     * RowGroups constructor.
     *
     * @param \ColbyGatte\SmartCsv\Row $row
     */
    public function __construct(Row $row)
    {
        $this->row = $row;
    }
    
    /**
     * @param $name
     *
     * @return array
     */
    public function __get($name)
    {
        return $this->row
            ->group($name);
    }
}