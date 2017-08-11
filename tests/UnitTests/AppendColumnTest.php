<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Csv\Slurp as CsvSlurp;
use PHPUnit\Framework\TestCase;

class AppendColumnTest extends TestCase
{
    /** @test */
    public function can_append_column()
    {
        $csv = new CsvSlurp;
        $csv->setSourceFile(SAMPLE_CSV);
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