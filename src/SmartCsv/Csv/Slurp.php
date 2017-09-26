<?php

namespace ColbyGatte\SmartCsv\Csv;

/**
 * Class Slurp
 * Reads all rows from CSV into memory.
 *
 * @package ColbyGatte\SmartCsv\Csv
 */
class Slurp extends Blank
{
    protected $csvSourceFile;

    /**
     * @param $file
     *
     * @return $this
     */
    public function setSourceFile($file)
    {
        $this->optionsParsed = true;

        $this->csvSourceFile = $file;

        $this->fileHandle = fopen($file, 'r');

        return $this->setHeader($this->readRow(false))
            ->read();
    }

    /**
     * Read all rows
     *
     * @return $this
     */
    protected function read()
    {
        while ($row = $this->readRow()) {
            array_push($this->rows, $row);
        }

        fclose($this->fileHandle);

        return $this;
    }
}