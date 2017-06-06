<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{
    /** @test */
    public function search_test_slurp()
    {
        $csv = csv([
            ['name', 'age'],
            ['Frankenstein', '26'],
            ['Sarah', '22'],
            ['Ben', '50']
        ]);

        $resultCsv = csv_search($csv, [
            function ($row) {
                return $row->age < 30;
            },
            function ($row) {
                return strlen($row->name) < 6;
            }
        ]);

        $resultCsv->write('/tmp/results.csv');

        $this->assertEquals(1, $resultCsv->countRows());

        $this->assertEquals(['Sarah', '22'], $resultCsv->first()
            ->toArray());
    }

    /** @test */
    public function search_test_sip()
    {
        $resultCsv = csv_search(csv_sip(SAMPLE_CSV), [
            function ($row) {
                return (int) $row->age > 70;
            }
        ]);

        $resultCsv->write('/tmp/results.csv');

        $this->assertEquals(9, $resultCsv->countRows());

        $this->assertEquals('Mrs. Emilie Pacocha Jr.', $resultCsv->first()->name);
    }

    /** @test */
    public function search_in_alter_mode_throws()
    {
        $csv = csv(['alter' => '__what.csv'], [
            ['name', 'age'],
            ['Frankenstein', '26'],
            ['Sarah', '22'],
            ['Ben', '50']
        ]);

        $threw = false;

        try {
            csv_search($csv, [
                function ($row) {
                    return $row->age < 30;
                },
                function ($row) {
                    return strlen($row->name) < 6;
                }
            ]);
        } catch (\Exception $e) {
            $threw = true;
        }

        $this->assertTrue($threw);
    }
}