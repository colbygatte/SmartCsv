<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /** @test */
    public function filter_test()
    {
        $path = '/tmp/dummy.csv';

        $csv = csv([
            ['name', 'age'],
            ['Colby', '26'],
            ['Sarah', '22'],
            ['Ben', '50']
        ]);

        $csv->addFilter(function ($row) {
            if ($row->name == 'Colby') {
                $row->name = strtoupper($row->name);
            }
        })
            ->addFilter(function ($row) {
                if ($row->name == 'Sarah') {
                    $row->age = 102510;
                }
            })
            ->addFilter(function ($row) {
                if ($row->name == 'Ben') {
                    $row->delete();
                }
            });

        $csv->runFilters()
            ->write($path);

        $this->assertEquals("name,age\nCOLBY,26\nSarah,102510\n", file_get_contents($path));
    }

    /** @test */
    public function can_pass_filters_through_cvs_helper_function()
    {
        $path = '/tmp/dummy.csv';

        $options = [
            'filters' => [
                function ($row) {
                    if ($row->name == 'Colby') {
                        $row->name = strtoupper($row->name);
                    }
                },
                function ($row) {
                    if ($row->name == 'Sarah') {
                        $row->age = 102510;
                    }
                },
                function ($row) {
                    if ($row->name == 'Ben') {
                        $row->delete();
                    }
                }
            ]
        ];

        $csv = csv($options,
            [
                ['name', 'age'],
                ['Colby', '26'],
                ['Sarah', '22'],
                ['Ben', '50']
            ]
        );

        $csv->runFilters()
            ->write($path);

        $this->assertEquals("name,age\nCOLBY,26\nSarah,102510\n", file_get_contents($path));
    }
}

