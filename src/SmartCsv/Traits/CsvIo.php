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
     * @param array|Row $data
     * @param resource  $fh
     */
    private function puts($data, $fh = null)
    {
        if ($data instanceof Row) {
            $data = $data->toArray(false);
        }

        fputcsv(
            $fh ?: $this->fileHandle,
            $data,
            $this->delimiter
        );
    }

    /**
     * @param bool $makeRow
     *
     * @return array|\ColbyGatte\SmartCsv\Row
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