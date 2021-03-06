<?php

namespace ColbyGatte\SmartCsv;

class Search
{
    /**
     * @var callable[]
     */
    protected $searchFilters = [];

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
     * @param callable[] $filters
     *
     * @return $this
     */
    public function addFilters($filters)
    {
        array_map([$this, 'addFilter'], $filters);

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