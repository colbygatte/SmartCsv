<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class AliasesTest extends TestCase
{
    /** @test */
    public function can_do_key_values_grouping()
    {
        $csv = csv([
            [
                'Specification 1', 'Value 1', 'UOM 1', 'Specification 2', 'Value 2', 'UOM 2', 'Specification 3',
                'Value 3', 'UOM 3'
            ],
            ['Length', '20', 'in', 'Height', '30', 'in', 'Weight', '100', 'lb']
        ]);

        $csv->columnGroup('specs', 'Specification', ['Value', 'UOM']);

        $data = $csv->first()->groups()->specs;

        $this->assertEquals(['Specification' => 'Height', 'Value' => '30', 'UOM' => 'in'], $data[1]);
    }

    /** @test */
    public function can_group_a_single_column()
    {
        $csv = csv([
            [
                'Specification 1', 'Value 1', 'UOM 1', 'Specification 2', 'Value 2', 'UOM 2', 'Specification 3',
                'Value 3', 'UOM 3'
            ],
            ['Length', '20', 'in', 'Height', '30', 'in', 'Weight', '100', 'lb']
        ]);

        $csv->columnGroup('specs', 'Specification');

        $data = $csv->first()->groups()->specs;

        $this->assertEquals(['Length', 'Height', 'Weight'], $data);
    }

    /** @test */
    public function grouping_works_with_index_aliases()
    {
        $csv = csv(['aliases' => ['a' => 'age']])->columnGroup('about', 'attribute', ['value', 'notes']);

        foreach ($csv->read(SAMPLE_CSV) as $row) {
            dump($row->groups()->baout);
        }
    }

}