<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Csv\Sip;
use PHPUnit\Framework\TestCase;

class OnlyAndExcludeTest extends TestCase
{
    /** @test */
    public function can_use_load_only_column_feature()
    {
        $csv = (new Sip)->setSourceFile(SAMPLE_CSV)->read()->only(['name', 'value 1']);
        
        $this->assertEquals(
            ['name', 'value 1'],
            array_keys($csv->first()->toArray())
        );
    }
}
