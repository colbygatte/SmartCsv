<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class AppendColumnTest extends TestCase
{
    /** @test */
    public function can_append_column()
    {
        $csv = csv(SAMPLE_CSV);
        
        $columnCount = $csv->columnCount();
        
        $csv->addColumn('Favorite coffee');
        
        $header = $csv->getHeader();
        
        $this->assertEquals(
            end($header),
            'Favorite coffee'
        );
        
        $this->assertEquals(
            $columnCount + 1,
            count($csv->first())
        );
    }
}