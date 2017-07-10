<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class CsvWriterTest extends TestCase
{
    /** @test */
    public function can_use_csv_writer()
    {
        csv_writer($file = '/tmp/writer_test.csv');
        
        $this->assertFileExists($file);
        
        unlink($file);
    }
}