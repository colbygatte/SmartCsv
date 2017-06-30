<?php

namespace ColbyGatte\SmartCsv\Traits;

use ColbyGatte\SmartCsv\Row;

/**
 * CSV Input/Output
 * @package ColbyGatte\SmartCsv\Traits
 */
trait CsvIo
{
    /**
     * @param      $data
     * @param bool $fh
     */
    private function puts($data, $fh = false)
    {
        if (! $fh) {
            $fh = $this->fileHandle;
        }

        if ($data instanceof Row) {
            $data = $data->toArray(false);
        }

        fputcsv($fh, $data, $this->delimiter);
    }

    /**
     * @param resource $fh
     *
     * @return \ColbyGatte\SmartCsv\Row|array
     */
    private function gets($makeRow = true)
    {
        $data = fgetcsv($this->fileHandle, 0, $this->delimiter);

        if ($makeRow && $data !== false) {
            return new Row($this, $data);
        }

        return $data;
    }
}