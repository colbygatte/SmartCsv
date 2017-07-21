<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Coders\CoderInterface;
use ColbyGatte\SmartCsv\Coders\WhitespaceTrimmer;
use ColbyGatte\SmartCsv\Csv\Blank;
use ColbyGatte\SmartCsv\Csv\Blank as BlankCsv;
use ColbyGatte\SmartCsv\Csv\Slurp;
use ColbyGatte\SmartCsv\Csv\Slurp as SlurpCsv;
use PHPUnit\Framework\TestCase;

class TestCoder implements CoderInterface
{
    public function encode($data)
    {
        return serialize($data);
    }
    
    public function decode($data)
    {
        return unserialize($data);
    }
}

class InvalidCoder
{
}

class CoderTest extends TestCase
{
    /** @test */
    public function can_use_encoder()
    {
        $csv = new SlurpCsv;
        $csv->setSourceFile(SAMPLE_CSV);
        $csv->read();
        
        $csv->addCoder('name', TestCoder::class)
            ->write($path = '/tmp/dummy_csv.csv');
        
        // ---
        
        $csv = new SlurpCsv;
        $csv->setSourceFile($path);
        $csv->read();
        
        $this->assertEquals(
            serialize('Prof. Adrian Schmeler IV'),
            $csv->first()->name
        );
    }
    
    /** @test */
    public function can_use_decoder()
    {
        // Setup
        $path = '/tmp/dummy_csv.csv';
        
        $csv = new SlurpCsv;
        $csv->setSourceFile(SAMPLE_CSV);
        $csv->read();
        
        $csv->addCoder('name', TestCoder::class)->write($path);
        
        // Assertion
        $this->assertEquals(
            'Prof. Adrian Schmeler IV',
            (new SlurpCsv)->addCoder('name', TestCoder::class)
                ->setSourceFile($path)
                ->read($path)
                ->first()
                ->name
        );
    }
    
    /** @test */
    public function cannot_use_invalid_coder()
    {
        // Setup
        $message = thrown_message(function () {
            (new BlankCsv)->addCoder('name', InvalidCoder::class);
        });
        
        // Assertion
        $this->assertEquals('Tests\UnitTests\InvalidCoder does not implement CoderInterface.', $message);
    }
    
    /** @test */
    public function can_user_coder_through_csv_helper_function()
    {
        // Setup
        $serialized = serialize(['name' => 'Colby']);
        
        $csv = (new BlankCsv)->setHeader(['data'])->addCoder('data', TestCoder::class)
            ->append([$serialized]);
        
        $csv->write($path = '/tmp/csv_helper_coder.csv');
        
        $csv->write($path = '/tmp/csv_helper_coder.csv');
        
        // Assertion
        $this->assertEquals(
            $serialized,
            (new SlurpCsv)->setSourceFile($path)->read()->first()->data
        );
    }
    
    /** @test */
    public function will_trim_whitespace()
    {
        $file = '/tmp/whitespace_test.csv';
        
        $csv = (new BlankCsv)->setHeader(['Name'])
            ->append(['   Colby   '])
            ->write($file);
        
        $this->assertEquals(
            'Colby',
            (new SlurpCsv)->setSourceFile($file)
                ->addCoder('Name', WhitespaceTrimmer::class)
                ->read()
                ->first()
                ->Name
        );
    }
    
    /** @test */
    public function coders_work_with_aliases() // TODO: implement
    {
        $file = '/tmp/whitespace_test.csv';
        
        (new Blank)->setHeader(['Name'])
            ->append(['   Colby   '])
            ->write($file);
        
        $this->assertEquals(
            'Colby',
            (new Slurp)->setAliases(['nm' => 'Name'])
                ->addCoder('nm', WhitespaceTrimmer::class)
                ->setSourceFile($file)
                ->read()
                ->first()->nm
        );
    }
}

