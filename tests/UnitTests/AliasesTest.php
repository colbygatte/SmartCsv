<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class AliasesTest extends TestCase
{
    /** @test */
    public function can_do_key_values_grouping()
    {
        $csv = csv()->setHeader([
            'Specification 1', 'Value 1', 'UOM 1', 'Specification 2', 'Value 2', 'UOM 2', 'Specification 3',
            'Value 3', 'UOM 3'])
            ->append(['Length', '20', 'in', 'Height', '30', 'in', 'Weight', '100', 'lb']);

        $csv->presets([
            'column-groups' => ['specs', 'Specification', ['Value', 'UOM']]
        ]);

        $data = $csv->first()->groups()->specs;

        $this->assertEquals(['Specification' => 'Height', 'Value' => '30', 'UOM' => 'in'], $data[1]);
    }

    /** @test */
    public function can_group_a_single_column()
    {
        $csv = csv()->setHeader([
            'Specification 1', 'Value 1', 'UOM 1', 'Specification 2', 'Value 2', 'UOM 2', 'Specification 3',
            'Value 3', 'UOM 3'])
            ->append(['Length', '20', 'in', 'Height', '30', 'in', 'Weight', '100', 'lb']);

        $csv->makeGroup('specs', 'Specification');

        $data = $csv->first()->groups()->specs;

        $this->assertEquals(['Length', 'Height', 'Weight'], $data);
    }
}