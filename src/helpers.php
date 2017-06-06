<?php

use ColbyGatte\SmartCsv\Csv;
use ColbyGatte\SmartCsv\Search;

if (! function_exists('csv')) {
    /**
     * If $file is string, the file will automatically be read.
     * If $file is an array of options, the options will be parsed.
     * If 'file' option is passed, the file will automatically be read and $rows will be ignored.
     *
     * @param string|array $file
     * @param array        $rows
     *
     * @return Csv
     */
    function csv($file = false, $rows = [])
    {
        $csv = new Csv();

        if (false === $file) {
            return $csv;
        }

        // Passing through multidimensional array will create Csv instance using array for rows
        if (is_array($file) && isset($file[0]) && is_array($file[0])) {
            $csv->setHeader(array_shift($file));
            return $csv->appendRows($file);
        }

        // If we are here, assume $file can be parsed by $csv->parseOptions()
        $csv->parseOptions($file);

        // If $csv->csvFile was set, read it!
        if ($csv->getCsvFile() !== false ) {
            // Return now to ensure ignoring $rows. If $rows is accidentally set,
            // it would override the header row from the original read above.
            return $csv->read();
        }

        if ($headerRow = array_shift($rows)) {
            $csv->setHeader($headerRow);
            $csv->appendRows($rows);
        }

        return $csv;
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

