<?php

use ColbyGatte\SmartCsv\Csv;
use ColbyGatte\SmartCsv\Search;

if (! function_exists('csv')) {
    /**
     * If $file is string, the file will automatically be read.
     * If $file is an array, the header and rows will be automatically populated.
     *
     * @param string|array $file
     * @param array $indexAliases
     *
     * @return Csv
     */
    function csv($file = false, $indexAliases = array())
    {
        $csv = new Csv();

        $csv->indexAliases = $indexAliases;

        // Passing through multidimensional array?
        if (is_array($file) && isset($file[0]) && is_array($file[0])) {
            $csv->setHeader(array_shift($file));

            foreach ($file as $row) {
                $csv->appendRow($row);
            }

            return $csv;
        }

        if (is_string($file) || is_array($file)) {
            return $csv->read($file);
        }

        return $csv;
    }
}

if (! function_exists('csv_search')) {
    /**
     * @param \ColbyGatte\SmartCsv\Csv $csv
     * @param callable[]               $filters
     *
     * @return mixed
     */
    function csv_search($csv, $filters)
    {
        $search = new Search;

        foreach ($filters as $filter) {
            $search->addFilter($filter);
        }

        return $csv->runSearch($search);
    }
}

