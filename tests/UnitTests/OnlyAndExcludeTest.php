<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class OnlyAndExcludeTest extends TestCase
{
    /** @test */
    public function can_use_load_only_column_feature()
    {
        $csv = csv_sip(SAMPLE_CSV)->only(['name', 'value 1']);
        
        $this->assertEquals(
            ['name', 'value 1'],
            array_keys($csv->first()->toArray())
        );
    }
}
