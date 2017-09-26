<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Csv\Sip;
use ColbyGatte\SmartCsv\RowDataCoders;
use PHPUnit\Framework\TestCase;

class CoderTest extends TestCase
{
    /** @test */
    public function can_use_decoder()
    {
        $coders = (new RowDataCoders)->setDecoder('name', function () {
            return 'NAME';
        });
        
        $csv = (new Sip)->setCoders($coders)->setSourceFile(SAMPLE_CSV);
        
        $row = $csv->current();
        
        $this->assertEquals('NAME', $row->name);
    }
}