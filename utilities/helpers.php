<?php

use ColbyGatte\SmartCsv\AbstractCsv;
use ColbyGatte\SmartCsv\Csv\Alter;
use ColbyGatte\SmartCsv\Csv\Blank;
use ColbyGatte\SmartCsv\Csv\Sip;
use ColbyGatte\SmartCsv\Csv\Slurp;
use ColbyGatte\SmartCsv\Csv\Writer;
use ColbyGatte\SmartCsv\CsvWriter;
use ColbyGatte\SmartCsv\Search;

if (! function_exists('csv')) {
    /**
     * Create a new Csv instance. If the file name is set in $options,
     * the file will automatically be read.
     *
     * @param string|array $options
     *
     * @return AbstractCsv
     */
    function csv($header)
    {
        $csv = (new Blank);
        
        if ($header) {
            $csv->setHeader($header);
        }
        
        return $csv;
    }
}

if (! function_exists('csv_slurp')) {
    /**
     * @param string $file
     *
     * @return \ColbyGatte\SmartCsv\AbstractCsv
     */
    function csv_slurp($file)
    {
        return (new Slurp)->setSourceFile($file)->read();
    }
}

if (! function_exists('csv_alter')) {
    /**
     * @param string $sourceFile
     * @param string $alterSourceFile
     *
     * @return \ColbyGatte\SmartCsv\AbstractCsv
     */
    function csv_alter($sourceFile, $alterSourceFile)
    {
        return (new Alter)->setSourceFile($sourceFile)->setAlterSourceFile($alterSourceFile)->read();
    }
}

if (! function_exists('csv_sip')) {
    /**
     * @param string $file
     *
     * @return \ColbyGatte\SmartCsv\AbstractCsv
     */
    function csv_sip($file)
    {
        return (new Sip)->setSourceFile($file)->read();
    }
}

if (! function_exists('csv_writer')) {
    /**
     * @param array $header
     *
     * @return \ColbyGatte\SmartCsv\CsvWriter
     */
    function csv_writer($writeTo, $header = null)
    {
        $writer = (new Writer)->setWriteFile($writeTo);
        
        if ($header) {
            $writer->setHeader($header);
        }
        
        return $writer;
    }
}

if (! function_exists('csv_search')) {
    /**
     * @param \ColbyGatte\SmartCsv\AbstractCsv $csv
     * @param callable[] $filters
     *
     * @return AbstractCsv
     */
    function csv_search($csv, $filters)
    {
        $csv = is_string($csv) ? csv_sip($csv) : $csv;
        
        return $csv->runSearch(
            (new Search)->addFilters($filters)
        );
    }
}

