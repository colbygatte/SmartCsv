<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Csv\Blank as BlankCsv;
use PHPUnit\Framework\TestCase;

class AliasesTest extends TestCase
{
    /** @test */
    public function can_do_key_values_grouping()
    {
        $csv = (new BlankCsv)->setHeader(['Specification 1', 'Value 1', 'UOM 1', 'Specification 2', 'Value 2', 'UOM 2', 'Specification 3', 'Value 3', 'UOM 3'])
            ->append(['Length', '20', 'in', 'Height', '30', 'in', 'Weight', '100', 'lb'])
            ->makeGroup('specs', 'Specification', ['Value', 'UOM']);
        
        $data = $csv->first()->groups()->specs;
        
        $this->assertEquals(['Specification' => 'Height', 'Value' => '30', 'UOM' => 'in'], $data[1]);
    }
    
    /** @test */
    public function can_group_a_single_column()
    {
        $csv = (new BlankCsv)->setHeader(['Specification 1', 'Value 1', 'UOM 1', 'Specification 2', 'Value 2', 'UOM 2', 'Specification 3', 'Value 3', 'UOM 3'])
            ->append(['Length', '20', 'in', 'Height', '30', 'in', 'Weight', '100', 'lb'])
            ->makeGroup('specs', 'Specification');
        
        $data = $csv->first()->groups()->specs;
        
        $this->assertEquals(['Length', 'Height', 'Weight'], $data);
    }
    
    /**
     * Test this alias
     *
     *  [
     *     'price' => 'reg_price',
     *     'special_price' => 'price'
     * ]
     *
     * @test
     */
    public function test_dup_aliases()
    {
        $this->assertEquals(
            'Invalid alias name(s) (alias is an existing header name): age',
            
            thrown_message(function () {
                csv_sip(SAMPLE_CSV)->setAliases([
                    'age' => 'name',
                    'special_age' => 'age'
                ]);
            })
        );
    }
}