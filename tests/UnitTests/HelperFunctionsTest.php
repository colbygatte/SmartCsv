<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class HelperFunctionsTest extends TestCase
{
    /** @test */
    public function can_use_csv_function_to_set_header()
    {
        $csv = csv(['name', 'age']);
        
        $this->assertEquals(
            ['name', 'age'],
            $csv->getHeader()
        );
    }
    
    // TODO: write test for setting source file after reading
    
    /** @test */
    public function can_use_csv_sip_function()
    {
        $this->assertEquals(
            'Prof. Adrian Schmeler IV',
            csv_sip(SAMPLE_CSV)->first()->name
        );
    }
}