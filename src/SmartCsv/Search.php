<?php

namespace ColbyGatte\SmartCsv;

class Search
{
    /**
     * @var callable[]
     */
    private $searchFilters = array();

    /**
     * @param callable $filter
     *
     * @return $this
     */
    public function addFilter($filter)
    {
        $this->searchFilters[] = $filter;

        return $this;
    }

    /**
     * @param \ColbyGatte\SmartCsv\Row $row
     *
     * @return bool
     */
    public function runFilters(Row $row)
    {
        foreach ($this->searchFilters as $searchFilter) {
            if (! $searchFilter($row)) {
                return false;
            }
        }

        return true;
    }
}