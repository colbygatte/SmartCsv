<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Coders\Serialize;
use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    // TODO: Write a test for index mappers

    /** @test */
    public function can_do_key_values_grouping()
    {
        $csv = csv(array(
            array('Specification 1', 'Value 1', 'UOM 1', 'Specification 2', 'Value 2', 'UOM 2', 'Specification 3', 'Value 3', 'UOM 3'),
            array('Length', '20', 'in', 'Height', '30', 'in', 'Weight', '100', 'lb')
        ));

        $data = $csv->first()
            ->groupColumns('Specification', ['Value', 'UOM']);

        $this->assertEquals(array('Specification' => 'Height', 'Value' => '30', 'UOM' => 'in'), $data[1]);
    }

    /** @test */
    public function index_aliases()
    {
        $csv = csv(
            array(
                array('Category', 'Product #'),
                array('flowers', '234234')
            ),
            // index aliases!
            array(
                'cat' => 'Category',
                'sku' => 'Product #'
            )
        );

        $this->assertEquals('234234', $csv->first()->sku);
    }

    /** @test */
    public function can_write_using_aliases_as_header_title()
    {
        csv(
            array(
                array('Category', 'Product #'),
                array('flowers', '234234')
            ),
            // index aliases!
            array(
                'cat' => 'Category',
                'sku' => 'Product #'
            )
        )->useAliases()
            ->write($path = '/tmp/dummy-csv.csv');

        $this->assertEquals('234234', csv($path)->first()->sku);
    }

    /** @test */
    public function can_write_csv()
    {
        quick_csv_ages($path = '/tmp/dummy_csv.csv');

        $csv = csv()->read($path);

        $this->assertEquals('Colby', $csv->first()->name);
    }

    /** @test */
    public function can_delete_row_from_csv()
    {
        quick_csv_ages($path = '/tmp/dummy_csv.csv');

        $csv = csv()->read($path);

        $csv->deleteRow(0);

        $csv->write($path);

        $this->assertEquals('Sarah', csv($path)->first()->name);
    }

    /** @test */
    public function can_edit_row_and_save()
    {
        quick_csv_ages($path = '/tmp/dummy_csv.csv');

        $csv = csv()->read($path);

        $csv->getRow(0)->name = 'Paul';

        $csv->write($path);

        $this->assertEquals('Paul', csv($path)->first()->name);
    }

    /** @test */
    public function can_delete_row_using_row_instance()
    {
        $csv = quick_csv_ages();

        $csv->first()
            ->delete();

        $this->assertEquals($csv->first()->name, 'Sarah');
    }
}
