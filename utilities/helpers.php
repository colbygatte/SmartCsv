<?php

use ColbyGatte\SmartCsv\Csv;
use ColbyGatte\SmartCsv\CsvWriter;
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
     * @param array $options
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
     * @param array $options
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
     * @param array $options
     *
     * @return \ColbyGatte\SmartCsv\Csv
     */
    function csv_sip($file, $options = [])
    {
        return csv(array_merge(['file' => $file, 'save' => false], $options));
    }
}

if (! function_exists('csv_writer')) {
    /**
     * @param string $file
     * @param array $header
     *
     * @return CsvWriter
     */
    function csv_writer($file, $header = null)
    {
        $csv_writer = (new CsvWriter)
            ->writeTo($file);
        
        return is_array($header)
            ? $csv_writer->setHeader($header)
            : $csv_writer;
    }
}

if (! function_exists('csv_search')) {
    /**
     * @param \ColbyGatte\SmartCsv\Csv|string $csv
     * @param callable[] $filters
     *
     * @return Csv
     */
    function csv_search($csv, $filters)
    {
        $csv = is_string($csv) ? csv_sip($csv) : $csv;
        
        return $csv->runSearch(
            (new Search)->addFilters($filters)
        );
    }
}

