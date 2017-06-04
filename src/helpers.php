<?php

use ColbyGatte\SmartCsv\Csv;

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