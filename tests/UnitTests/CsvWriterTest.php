<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\CsvWriter;
use PHPUnit\Framework\TestCase;

class CsvWriterTest extends TestCase
{
    /** @test */
    public function can_use_csv_writer()
    {
        (new CsvWriter)->writeTo($file = '/tmp/writer_test.csv');
        
        $this->assertFileExists($file);
    }
}