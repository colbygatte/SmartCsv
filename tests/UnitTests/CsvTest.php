<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Coders\Serialize;
use ColbyGatte\SmartCsv\Csv\Alter;
use ColbyGatte\SmartCsv\Csv\Blank;
use ColbyGatte\SmartCsv\Csv\Sip;
use ColbyGatte\SmartCsv\Csv\Slurp;
use ColbyGatte\SmartCsv\Search;
use PHPUnit\Framework\TestCase;

class CsvTest extends TestCase
{
    /** @test */
    public function index_aliases()
    {
        $csv = (new Blank)->setAliases(['cat' => 'Category', 'sku' => 'Product #'])
            ->setHeader(['Category', 'Product #'])
            ->append(['flowers', '234234']);
        
        $csv->first()
            ->toArray(true);
        
        $this->assertEquals('234234', $csv->first()->sku);
    }
    
    /** @test */
    public function can_write_using_aliases_as_header_title()
    {
        (new Blank)
            ->setAliases(['cat' => 'Category', 'sku' => 'Product #'])
            ->setHeader(['Category', 'Product #'])
            ->append(['flowers', '234234'])
            ->useAliases()
            ->write($path = '/tmp/dummy-csv.csv');
        
        $this->assertEquals('234234', (new Slurp)->setSourceFile($path)->read()->first()->sku);
    }
    
    /** @test */
    public function can_write_csv()
    {
        // Setup
        (new Blank)
            ->setHeader(['name', 'age'])
            ->append(['Colby', '25'], ['Sarah', '22'])
            ->write($path = '/tmp/ages.csv');
        
        // Assertion
        $this->assertEquals(
            'Colby',
            (new Slurp)->setSourceFile($path)->read()->first()->name
        );
    }
    
    /** @test */
    public function can_delete_row_from_csv()
    {
        (new Blank)
            ->setHeader(['name', 'age'])
            ->append(['Colby', '25'], ['Sarah', '22'])
            ->write($path = '/tmp/dummy_csv.csv');
        
        $csv = (new Slurp)->setSourceFile($path)->read();
        
        $csv->first()
            ->delete();
        
        $csv->write($path);
        
        $this->assertEquals('Sarah', (new Slurp)->setSourceFile($path)->read()->first()->name);
    }
    
    /** @test */
    public function can_edit_row_and_save()
    {
        (new Blank)
            ->setHeader(['name', 'age'])
            ->append(['Colby', '25'], ['Sarah', '22'])
            ->write($path = '/tmp/dummy_csv.csv');
        
        $csv = (new Slurp)->setSourceFile($path)->read();
        
        $csv->first()->name = 'Paul';
        
        $csv->write($path);
        
        $this->assertEquals(
            'Paul',
            (new Slurp)->setSourceFile($path)->read()->first()->name
        );
    }
    
    /** @test */
    public function can_delete_row_using_row_instance()
    {
        $csv = (new Blank)
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
        $csv = (new Blank)
            ->setAliases(['shortstring' => 'A Really Long String Of Text'])
            ->setHeader(['A Really Long String Of Text'])
            ->append(['I LOVE PHP'], ['WOOOOOOOOO']);
        
        $csv->each(function ($row) {
            $row->shortstring = strtolower($row->shortstring);
        });
        
        // Index aliases can also be used in place of the original column name when writing
        $csv->useAliases()
            ->write('/tmp/using_aliases.csv');
        
        $this->assertEquals(
            'i love php',
            (new Slurp)->setSourceFile('/tmp/using_aliases.csv')->read()->first()->shortstring
        );
    }
    
    /** @test */
    public function can_iterate_and_alter_each_row_and_save_to_new_file()
    {
        // Delete the row with name 'Kyra Stevens'
        // Change all emails to 'nocontact'
        $alter = (new Alter)
            ->setSourceFile(SAMPLE_CSV)
            ->setAlterSourceFile($savePath = '/tmp/iterated.csv')
            ->read();
        
        foreach ($alter as $row) {
            if ($row->name == 'Mrs. Emilie Pacocha Jr.') {
                $row->delete();
                continue;
            }
            
            $row->age = 'noage';
        }
        
        $csv = (new Slurp)->setSourceFile($savePath)->read();
        
        $search = (new Search)->addFilter(function ($row) {
            return $row->name == 'Mrs. Emilie Pacocha Jr.';
        });
        
        $this->assertEquals(
            0,
            $csv->runSearch($search)->count()
        );
        
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
        
        foreach ((new Slurp)->setSourceFile(SAMPLE_CSV)->read() as $row) {
            $ages[] = $row->age;
        }
        
        $agesFromIterate = [];
    
        $csv = (new Sip)->setSourceFile(SAMPLE_CSV)->read();
        
        foreach ($csv as $row) {
            $agesFromIterate[] = $row->age;
        }
        
        $this->assertEquals($ages, $agesFromIterate);
    }
    
    /** @test */
    public function can_change_delimiter()
    {
        // Setup
        (new Blank)
            ->setHeader(['name', 'age'])
            ->setDelimiter('|')
            ->write($path = '/tmp/changing_delimiter.csv');
        
        // Assertion
        $this->assertEquals("name|age\n", file_get_contents($path));
        
        $this->assertEquals(
            ['name', 'age'],
            (new Slurp)->setSourceFile($path)->setDelimiter('|')->read()->getHeader()
        );
    }
    
    /** @test */
    public function cannot_set_header_twice()
    {
        $this->assertEquals(
            'Header can only be set once!',
            thrown_message(function () {
                (new Blank)->setHeader(['hi'])->setHeader(['hi']);
            })
        );
    }
    
    /** @test */
    public function header_must_be_set_before_adding_rows()
    {
        $m = thrown_message(function () {
            (new Blank)->append(['hi']);
        });
        
        $this->assertEquals('Header must be set before adding rows!', $m);
    }
    
    /** @test */
    public function adding_row_with_incorrect_amount_of_columns_appends_extra_columns()
    {
        $csv = (new Blank)
            ->setStrictMode(false)
            ->setHeader(['one', 'two', 'three'])
            ->append(['one']);
        
        $this->assertCount(3, $csv->first());
    }
    
    /** @test */
    public function cannot_read_row_with_too_few_columns_in_strict_mode()
    {
        $m = thrown_message(function () {
            (new Blank)->setHeader(['one', 'two', 'three'])->append(['hi']);
        });
        
        $this->assertEquals('Expected 3 data entry(s), received 1. (no file set)', $m);
    }
    
    /** @test */
    public function can_check_if_csv_has_columns()
    {
        $csv = (new Slurp)->setSourceFile(SAMPLE_CSV)->read();
        
        $this->assertEmpty($csv->missingColumns(['name', 'age']));
        
        $this->assertNotEmpty($csv->missingColumns(['phone number', 'social security number']));
    }
    
    /** @test */
    public function cannot_add_more_entries_than_columns()
    {
        $m = thrown_message(function () {
            (new Blank)->setHeader(['just one'])->append(['one', 'two']);
        });
        
        $this->assertEquals('Expected 1 data entry(s), received 2. (no file set)', $m);
    }
    
    /** @test */
    public function column_headers_must_all_be_unique()
    {
        $m = thrown_message(function () {
            (new Blank)->setHeader(['Hi', 'Hi'])->getHeader();
        });
        
        $this->assertEquals('Duplicate headers: Hi', $m);
    }
    
    /** Disabled for now. Not implemented. */
    public function csv_search_rows_are_clone()
    {
        $orig = (new Blank)
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
        $csv = (new Blank)
            ->setHeader(['name', 'age', 'weight'])
            ->append(['Colby', '23', '230']);
        
        $data = $csv->first()
            ->pluck(['age', 'weight']);
        
        $this->assertEquals(['age' => '23', 'weight' => '230'], $data);
    }
    
    /** @test */
    public function can_use_first_function_when_in_sip_mode()
    {
        $this->assertNotFalse(
            (new Slurp)->setSourceFile(SAMPLE_CSV)->read()->first()
        );
    }
    
    /** @test */
    public function set_header_thrown_error_is_caught_and_re_throws_more_useful_error_message()
    {
        touch($emptyFile = '/tmp/empty-file.csv');
        
        $this->assertEquals('Error setting CSV header: Header must be an array.', thrown_message(function () use ($emptyFile) {
            (new Sip)->setSourceFile($emptyFile)->read();
        }));
    }
    
    /** @test */
    public function can_add_coder_after_calling_csv_sip()
    {
        $csv = (new Sip)->setSourceFile(SAMPLE_CSV)->read()->addCoder('other_info', Serialize::class);
        
        $this->assertTrue(is_array($csv->first()->other_info));
    }
}
