<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Csv\Writer;
use PHPUnit\Framework\TestCase;

class CsvWriterTest extends TestCase
{
    /** @test */
    public function can_use_csv_writer()
    {
        $csvWriter = new Writer;
        
        $csvWriter->setWriteFile($file = '/tmp/writer_test.csv');
        
        $this->assertFileExists($file);
        
        unlink($file);
    }
}