<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    /** @test */
    public function index_aliases()
    {
        $csv = csv([
            'aliases' => ['cat' => 'Category', 'sku' => 'Product #']
        ], [
                ['Category', 'Product #'],
                ['flowers', '234234']
            ]);

        $this->assertEquals('234234', $csv->first()->sku);
    }

    /** @test */
    public function can_write_using_aliases_as_header_title()
    {
        csv([
            'aliases' => ['cat' => 'Category', 'sku' => 'Product #']
        ], [
                ['Category', 'Product #'],
                ['flowers', '234234']
            ])
            ->useAliases()
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

        $csv = csv($path);

        $csv->first()
            ->delete();

        $csv->write($path);

        $this->assertEquals('Sarah', csv($path)->first()->name);
    }

    /** @test */
    public function can_edit_row_and_save()
    {
        quick_csv_ages($path = '/tmp/dummy_csv.csv');

        $csv = csv($path);

        $csv->first()->name = 'Paul';

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

    /** @test */
    public function can_change_value_using_alias()
    {
        $this->assertTrue(true);

        $csv = csv([
            'aliases' => ['shortstring' => 'A Really Long String Of Text']
        ], [
                ['A Really Long String Of Text'],
                ['I LOVE PHP'],
                ['WOOOOOOOOO']
            ]);

        $csv->each(function ($row) {
            $row->shortstring = strtolower($row->shortstring);
        });

        // Index aliases can also be used in place of the original column name when writing
        $csv->useAliases()
            ->write('/tmp/using_aliases.csv');

        $this->assertEquals('i love php', csv('/tmp/using_aliases.csv')->first()->shortstring);
    }

    /** @test */
    public function can_iterate_and_alter_each_row_and_save_to_new_file()
    {
        $options = [
            'file' => SAMPLE_CSV,
            'alter' => $savePath = '/tmp/iterated.csv'
        ];

        // Delete the row with name 'Kyra Stevens'
        // Change all emails to 'nocontact'
        foreach (csv($options) as $row) {
            if ($row->name == 'Mrs. Emilie Pacocha Jr.') {
                $row->delete();

                continue;
            }

            $row->age = 'noage';
        }

        $csv = csv($savePath);

        $this->assertCount(0, $csv->findRows('name', 'Mrs. Emilie Pacocha Jr.'));

        $ages = [];

        foreach ($csv as $row) {
            $ages[] = $row->age;
        }


        $this->assertEquals(['noage'], array_keys(array_flip($ages)));
    }

    /** @test */
    public function can_iterate_line_by_line()
    {
        $ages = [];

        foreach (sample_csv() as $row) {
            $ages[] = $row->age;
        }

        $agesFromIterate = [];

        $csv = csv(['file' => SAMPLE_CSV, 'save' => false]);

        foreach ($csv as $row) {
            $agesFromIterate[] = $row->age;
        }

        $this->assertEquals($ages, $agesFromIterate);

        $this->assertEquals(0, $csv->countRows());
    }

    /** @test */
    public function can_change_delimiter()
    {
        $path = '/tmp/changing_delimiter.csv';

        csv(['del' => '|'])
            ->setHeader(['name', 'age'])
            ->write($path);

        $this->assertEquals("name|age\n", file_get_contents($path));

        $this->assertEquals(['name', 'age'], csv(['file' => $path, 'del' => '|'])->getHeader());
    }
}
