<?php

namespace ColbyGatte\SmartCsv\Csv;

use ColbyGatte\SmartCsv\Row;

/**
 * Class Writer
 * Used for writing to a CSV file.
 *
 * @package ColbyGatte\SmartCsv\Csv
 */
class Writer
{
    /**
     * @var bool
     */
    protected $didSetHeader = false;

    /**
     * The CSV file handle.
     * $this->gets() and $this->puts() read from here if
     * no file handle is given.
     *
     * @var resource|bool
     */
    protected $fileHandle;

    /**
     * @var string
     */
    protected $delimiter = ',';

    /** @var string */
    protected $writeMode = 'w';

    /**
     * @param $file
     *
     * @return $this
     * @throws \Exception
     */
    public function setWriteFile($file)
    {
        if (is_resource($file)) {
            $this->fileHandle = $file;

            return $this;
        }

        if (! touch($file)) {
            throw new \Exception("$file is not writable.");
        }

        $this->fileHandle = fopen($file, $this->writeMode);

        return $this;
    }

    public function setWriteMode($writeMode)
    {
        $this->writeMode = $writeMode;

        return $this;
    }

    /**
     * @param $header
     *
     * @return $this
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    public function writeHeader($header)
    {
        if ($this->didSetHeader) {
            throw new Exception('Header has already been set');
        }

        $this->didSetHeader = true;

        $this->writeRow($header);

        return $this;
    }

    /**
     * @param array ...$rows
     *
     * @return $this
     */
    public function append(...$rows)
    {
        array_map([$this, 'writeRow'], $rows);

        return $this;
    }

    /**
     * @param array|Row $data
     * @param resource  $fh
     */
    protected function writeRow($data, $fh = null)
    {
        fputcsv(
            $fh ?: $this->fileHandle,
            $data instanceof Row ? $data->toEncodedArray() : $data,
            $this->delimiter
        );
    }

    /**
     * @param bool $makeRow
     *
     * @return array|\ColbyGatte\SmartCsv\Row
     */
    protected function readRow($makeRow = true)
    {
        $data = fgetcsv($this->fileHandle, 0, $this->delimiter);

        if ($makeRow && $data !== false) {
            $row = new Row($this, $data);

            return $row;
        }

        return $data;
    }
}