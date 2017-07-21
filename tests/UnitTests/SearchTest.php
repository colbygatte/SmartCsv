<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Csv\Alter;
use ColbyGatte\SmartCsv\Csv\Blank;
use ColbyGatte\SmartCsv\Csv\Slurp;
use ColbyGatte\SmartCsv\Search;
use PHPUnit\Framework\TestCase;
use ColbyGatte\SmartCsv\Exception;

class SearchTest extends TestCase
{
    /** @test */
    public function search_test_slurp()
    {
        $csv = (new Blank)
            ->setHeader(['name', 'age'])
            ->append(['Frankenstein', '26'], ['Sarah', '22'], ['Ben', '50']);
        
        $search = (new Search)
            ->addFilter(function ($row) {
                return $row->age < 30;
            })
            ->addFilter(function ($row) {
                return strlen($row->name) < 6;
            });
        
        $resultCsv = $csv->runSearch($search);
        
        $resultCsv->write('/tmp/results.csv');
        
        $this->assertEquals(1, $resultCsv->count());
        
        $this->assertEquals(['name' => 'Sarah', 'age' => '22'], $resultCsv->first()
            ->toArray());
    }
    
    /** @test */
    public function search_test_sip()
    {
        $search = (new Search)->addFilter(function ($row) {
            return (int) $row->age > 70;
        });
        
        $resultCsv = (new Slurp)->setSourceFile(SAMPLE_CSV)->read()->runSearch($search);
        
        $this->assertEquals(4, $resultCsv->count());
        
        $this->assertEquals('Bernardo Turcotte', $resultCsv->first()->name);
    }
    
    /** @test */
    public function search_in_alter_mode_throws()
    {
        $this->assertNotEmpty(
            thrown_message(function () {
                $search = (new Search)
                    ->addFilter(function ($row) {
                        return $row->age < 30;
                    })
                    ->addFilter(function ($row) {
                        return strlen($row->name) < 6;
                    });
                
                $result = (new Alter)
                    ->setSourceFile(SAMPLE_CSV)
                    ->setAlterSourceFile('/tmp/alter-file.csv')
                    ->runSearch($search);
                
                return $result;
            })
        );
    }
    
    /** @test */
    public function match_up()
    {
        $csv = (new Blank)->setHeader(['awesome_human', 'awesome_email', 'value 2'])
            ->setStrictMode(false)
            ->append(
                ['Bernardo Turcotte', 'sloot@sllootsrus.com'],
                ['Prof. Gregorio Schowalter Sr.', 'lrunte@hotmail.com', 'sushi']
            );
        
        $resultCsv = (new Slurp)->setSourceFile(SAMPLE_CSV)->read()->findMatches($csv, [
            'name' => 'awesome_human',
            'contact 1' => 'awesome_email',
            'value 2' => 'value 2'
        ]);
        
        $this->assertEquals(2, $resultCsv->count());
    }
}
