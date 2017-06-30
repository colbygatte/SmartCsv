<?php

use ColbyGatte\SmartCsv\Csv;
use ColbyGatte\SmartCsv\Search;

if (! function_exists('csv')) {
    /**
     * If $file is string, the file will automatically be read.
     * If $file is an array of options, the options will be parsed.
     * If 'file' option is passed, the file will automatically be read and $rows will be ignored.
     *
     * @param string|array $options
     * @param array        $rows
     *
     * @return Csv
     */
    function csv($options = false)
    {
        $csv = new Csv;

        if (false === $options) {
            return $csv;
        }

        // If we are here, assume $file can be parsed by $csv->parseOptions()
        $csv->parseOptions($options);

        // If $csv->csvFile was set, read it!
        if ($csv->getFile() !== false ) {
            // Return now to ensure ignoring $rows. If $rows is accidentally set,
            // it would override the header row from the original read above.
            return $csv->read();
        }

        return $csv;
    }
}

if (! function_exists('csv_slurp')) {
    function csv_slurp($file, $options = [])
    {
        return csv(array_merge(['file' => $file], $options));
    }
}

if (! function_exists('csv_alter')) {
    function csv_alter($file, $writeTo, $options = [])
    {
        return csv(array_merge(['file' => $file, 'alter' => $writeTo], $options));
    }
}

if (! function_exists('csv_sip')) {
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

