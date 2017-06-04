<?php

namespace ColbyGatte\SmartCsv;

class Search
{
    /**
     * @var callable[]
     */
    private $searchFilters = array();

    public function addFilter($filter)
    {
        $this->searchFilters[] = $filter;

        return $this;
    }

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