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
            ->setUseAliases()
            ->write($path = '/tmp/dummy-csv.csv');

        $this->assertEquals('234234', csv($path)->first()->sku);
    }

    /** @test */
    public function can_write_csv()
    {
        csv()
            ->setHeader(['name', 'age'])
            ->append(['Colby', '25'], ['Sarah', '22'])
            ->write($path = '/tmp/ages.csv');

        $this->assertEquals('Colby', csv($path)->first()->name);
    }

    /** @test */
    public function can_delete_row_from_csv()
    {
        csv()
            ->setHeader(['name', 'age'])
            ->append(['Colby', '25'], ['Sarah', '22'])
            ->write($path = '/tmp/dummy_csv.csv');

        $csv = csv($path);

        $csv->first()
            ->delete();

        $csv->write($path);

        $this->assertEquals('Sarah', csv($path)->first()->name);
    }

    /** @test */
    public function can_edit_row_and_save()
    {
        csv()
            ->setHeader(['name', 'age'])
            ->append(['Colby', '25'], ['Sarah', '22'])
            ->write($path = '/tmp/dummy_csv.csv');

        $csv = csv($path);

        $csv->first()->name = 'Paul';

        $csv->write($path);

        $this->assertEquals('Paul', csv($path)->first()->name);
    }

    /** @test */
    public function can_delete_row_using_row_instance()
    {
        $csv = csv()
            ->setHeader(['name', 'age'])
            ->append(['Colby', '25'], ['Sarah', '22']);

        $csv->write('/tmp/dummy_csv.csv');

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
        $csv->setUseAliases()
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

        foreach (csv(SAMPLE_CSV) as $row) {
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
        csv()
            ->setHeader(['name', 'age'])
            ->parseOptions(['del' => '|'])
            ->write($path = '/tmp/changing_delimiter.csv');

        $this->assertEquals("name|age\n", file_get_contents($path));

        $this->assertEquals(['name', 'age'], csv(['file' => $path, 'del' => '|'])->getHeader());
    }

    /** @test */
    public function cannot_set_header_twice()
    {
        $this->assertEquals(
            'Header can only be set once!',
            thrown_message(function () {
                csv()->setHeader(['hi'])->setHeader(['hi']);
            })
        );
    }

    /** @test */
    public function header_must_be_set_before_adding_rows()
    {
        $m = thrown_message(function () {
            csv()->append(['hi']);
        });

        $this->assertEquals('Header must be set before adding rows!', $m);
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
        $m = thrown_message(function () {
            csv()->setHeader(['one', 'two', 'three'])->append(['hi']);
        });

        $this->assertEquals('Expected 3 data entry(s), received 1.', $m);
    }

    /** @test */
    public function can_check_if_csv_has_columns()
    {
        $csv = csv(SAMPLE_CSV);

        $this->assertEmpty($csv->missingColumns(['name', 'age']));

        $this->assertNotEmpty($csv->missingColumns(['phone number', 'social security number']));
    }

    /** @test */
    public function cannot_add_more_entries_than_columns()
    {
        $m = thrown_message(function () {
            csv()
                ->setHeader(['just one'])
                ->append(['one', 'two']);
        });

        $this->assertEquals('Expected 1 data entry(s), received 2.', $m);
    }

    /** @test */
    public function column_headers_must_all_be_unique()
    {
        $m = thrown_message(function () {
            csv()->setHeader(['Hi', 'Hi'])->getHeader();
        });

        $this->assertEquals('Duplicate headers: Hi', $m);
    }

    /** Disabled for now. Not implemented. */
    public function csv_search_rows_are_clone()
    {
        $orig = csv()
            ->setHeader(['name'])
            ->append(['Colby']);

        $results = csv_search($orig, [
            function ($row) {
                $row->name = "Tara";

                return true;
            }
        ]);

        $this->assertEquals('Colby', $orig->first()->name);

        $this->assertEquals('Tara', $results->first()->name);
    }

    /** @test */
    function can_pluck_data()
    {
        $csv = csv()
            ->setHeader(['name', 'age', 'weight'])
            ->append(['Colby', '23', '230']);

        $data = $csv->first()
            ->pluck(['age', 'weight']);

        $this->assertEquals(['age' => '23', 'weight' => '230'], $data);
    }

    /** @test */
    public function can_use_first_function_when_in_sip_mode()
    {
        $this->assertNotFalse(csv_sip(SAMPLE_CSV)->first());
    }
}
