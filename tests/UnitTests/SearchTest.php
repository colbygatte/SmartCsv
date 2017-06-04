<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Search;
use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{
    /** @test */
    public function search_test()
    {
        $csv = csv(array(
            array('name', 'age'),
            array('Frankenstein', '26'),
            array('Sarah', '22'),
            array('Ben', '50')
        ));

        $resultCsv = csv_search($csv, array(
            function ($row) {
                return $row->age < 30;
            },
            function ($row) {
                return strlen($row->name) < 6;
            }
        ));

        $resultCsv->write('/tmp/results.csv');

        $this->assertEquals(1, $resultCsv->countRows());

        $this->assertEquals(['Sarah', '22'], $resultCsv->first()
            ->toArray());
    }
}