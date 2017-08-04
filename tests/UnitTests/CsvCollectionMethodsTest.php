<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class CsvCollectionMethodsTest extends TestCase
{
    /** @test */
    public function can_pluck_column()
    {
        $ages = csv_sip(SAMPLE_CSV)->pluck('age');
        
        $this->assertEquals(
            ['31', '23', '62'],
            
            array_slice($ages, 0, 3)
        );
    }
    
    /** @test */
    public function can_map_csv()
    {
        $mapped = csv_sip(SAMPLE_CSV)->map(function ($row) {
            return [$row->name, $row->age];
        });
        
        $this->assertEquals(
            [
                ["Prof. Adrian Schmeler IV", "31"],
                ["Jarred Lowe", "23"],
                ["Kaya Nikolaus", "62"]
            ],
            array_slice($mapped, 0, 3)
        );
    }
}