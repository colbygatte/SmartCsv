<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{
    /** @test */
    public function search_test_slurp()
    {
        $csv = csv()
            ->setHeader(['name', 'age'])
            ->append(['Frankenstein', '26'], ['Sarah', '22'], ['Ben', '50']);
        
        $resultCsv = csv_search($csv, [
            function ($row) {
                return $row->age < 30;
            },
            function ($row) {
                return strlen($row->name) < 6;
            }
        ]);
        
        $resultCsv->write('/tmp/results.csv');
        
        $this->assertEquals(1, $resultCsv->count());
        
        $this->assertEquals(['name' => 'Sarah', 'age' => '22'], $resultCsv->first()
            ->toArray());
    }
    
    /** @test */
    public function search_test_sip()
    {
        $resultCsv = csv_search(SAMPLE_CSV, [
            function ($row) {
                return (int) $row->age > 70;
            }
        ]);
        
        $resultCsv->write('/tmp/results.csv');
        
        $this->assertEquals(4, $resultCsv->count());
        
        $this->assertEquals('Bernardo Turcotte', $resultCsv->first()->name);
    }
    
    /** @test */
    public function search_in_alter_mode_throws()
    {
        $this->assertNotEmpty(
            thrown_message(function () {
                csv_search(csv_alter(SAMPLE_CSV, '/tmp/alter-file.csv'), [
                    function ($row) {
                        return $row->age < 30;
                    },
                    function ($row) {
                        return strlen($row->name) < 6;
                    }
                ]);
            })
        );
    }
    
    /** @test */
    public function match_up()
    {
        $csv = csv()->setHeader(['awesome_human', 'awesome_email', 'value 2'])
            ->setStrictMode(false)
            ->append(
                ['Bernardo Turcotte', 'sloot@sllootsrus.com'],
                ['Prof. Gregorio Schowalter Sr.', 'lrunte@hotmail.com', 'sushi']
            );
        
        $resultCsv = csv(SAMPLE_CSV)->findMatches($csv, [
            'name' => 'awesome_human',
            'contact 1' => 'awesome_email',
            'value 2' => 'value 2'
        ]);
        
        $this->assertEquals(2, $resultCsv->count());
    }
}
