<?php

use ColbyGatte\SmartCsv\Csv;
use ColbyGatte\SmartCsv\Search;

if (! function_exists('csv')) {
    /**
     * Create a new Csv instance. If the file name is set in $options,
     * the file will automatically be read.
     *
     * @param string|array $options
     *
     * @return Csv
     */
    function csv($options = [])
    {
        $csv = (new Csv)->parseOptions($options);

        return $csv->getFile() ? $csv->read() : $csv;
    }
}

if (! function_exists('csv_slurp')) {
    /**
     * @param string $file
     * @param array  $options
     *
     * @return \ColbyGatte\SmartCsv\Csv
     */
    function csv_slurp($file, $options = [])
    {
        return csv(array_merge(['file' => $file], $options));
    }
}

if (! function_exists('csv_alter')) {
    /**
     * @param string $csv
     * @param string $writeTo
     * @param array  $options
     *
     * @return \ColbyGatte\SmartCsv\Csv
     */
    function csv_alter($csv, $writeTo, $options = [])
    {
        return csv(array_merge(['file' => $csv, 'alter' => $writeTo], $options));
    }
}

if (! function_exists('csv_sip')) {
    /**
     * @param string $file
     * @param array  $options
     *
     * @return \ColbyGatte\SmartCsv\Csv
     */
    function csv_sip($file, $options = [])
    {
        return csv(array_merge(['file' => $file, 'save' => false], $options));
    }
}

if (! function_exists('csv_search')) {
    /**
     * @param \ColbyGatte\SmartCsv\Csv $csv
     * @param callable[]               $filters
     *
     * @return Csv
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

