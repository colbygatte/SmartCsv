<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    /** @test */
    public function index_aliases()
    {
        $csv = csv(['aliases' => ['cat' => 'Category', 'sku' => 'Product #']])
            ->setHeader(['Category', 'Product #'])
            ->append(['flowers', '234234']);

        $csv->first()
            ->toArray(true);

        $this->assertEquals('234234', $csv->first()->sku);
    }

    /** @test */
    public function can_write_using_aliases_as_header_title()
    {
        csv([
            'aliases' => ['cat' => 'Category', 'sku' => 'Product #']
        ])
            ->setHeader(['Category', 'Product #'])
            ->append(['flowers', '234234'])
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
        ])
            ->setHeader(['A Really Long String Of Text'])
            ->append(['I LOVE PHP'], ['WOOOOOOOOO']);

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

        $count = csv_search($csv, [
            function ($row) {
                return $row->name == 'Mrs. Emilie Pacocha Jr.';
            }
        ])->count();

        $this->assertEquals(0, $count);

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

        $this->assertEquals(0, $csv->count());
    }

    /** @test */
    public function can_change_delimiter()
    {
        $path = '/tmp/changing_delimiter.csv';

        csv()
            ->setHeader(['name', 'age'])
            ->presets(['del' => '|'])
            ->write($path);

        $this->assertEquals("name|age\n", file_get_contents($path));

        $this->assertEquals(['name', 'age'], csv(['file' => $path, 'del' => '|'])->getHeader());
    }

    /** @test */
    public function cannot_set_header_twice()
    {
        $error = get_thrown_message(function () {
            csv()
                ->setHeader(['hi'])
                ->setHeader(['hi']);
        });

        $this->assertEquals('Header can only be set once!', $error);
    }

    /** @test */
    public function header_must_be_set_before_adding_rows()
    {
        $error = get_thrown_message(function () {
            csv()->append(['hi']);
        });

        $this->assertEquals('Header must be set before adding rows!', $error);
    }

    /** @test */
    public function adding_row_with_incorrect_amount_of_columns_appends_extra_columns()
    {
        $csv = csv()
            ->setStrictMode(false)
            ->setHeader(['one', 'two', 'three'])
            ->append(['one']);

        $this->assertCount(3, $csv->first());
    }

    /** @test */
    public function cannot_read_row_with_too_few_columns_in_strict_mode()
    {
        $m = get_thrown_message(function () {
            csv()
                ->setHeader(['one', 'two', 'three'])
                ->append(['hi']);
        });

        $this->assertEquals('Expected 3 data entry(s), received 1.', $m);
    }

    /** @test */
    public function can_check_if_csv_has_columns()
    {
        $csv = csv(SAMPLE_CSV);

        $this->assertEmpty($csv->hasColumns(['name', 'age']));

        $this->assertNotEmpty($csv->hasColumns(['phone number', 'social security number']));
    }

    /** @test */
    public function cannot_add_more_entries_than_columns()
    {
        $error = get_thrown_message(function () {
            csv()
                ->setHeader(['just one'])
                ->append(['one', 'two']);
        });

        $this->assertEquals('Expected 1 data entry(s), received 2.', $error);
    }

    /** @test */
    public function column_headers_must_all_be_unique()
    {
        $e = get_thrown_message(function() {
            csv()->setHeader(['Hi', 'Hi'])->getHeader();
        });

        $this->assertEquals('Column titles must be unique.', $e);
    }
}
