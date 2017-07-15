<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Coders\CoderInterface;
use ColbyGatte\SmartCsv\Coders\WhitespaceTrimmer;
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
        csv(SAMPLE_CSV)
            ->addCoder('name', TestCoder::class)
            ->write($path = '/tmp/dummy_csv.csv');
        
        $this->assertEquals(serialize('Prof. Adrian Schmeler IV'), csv($path)->first()->name);
    }
    
    /** @test */
    public function can_use_decoder()
    {
        $path = '/tmp/dummy_csv.csv';
        
        csv(SAMPLE_CSV)->addCoder('name', TestCoder::class)->write($path);
        
        $this->assertEquals(
            'Prof. Adrian Schmeler IV',
            csv()->addCoder('name', TestCoder::class)
                ->read($path)
                ->first()->name
        );
    }
    
    /** @test */
    public function cannot_use_invalid_coder()
    {
        $message = thrown_message(function () {
            csv(SAMPLE_CSV)->addCoder('name', InvalidCoder::class);
        });
        
        $this->assertEquals('Tests\UnitTests\InvalidCoder does not implement CoderInterface.', $message);
    }
    
    /** @test */
    public function can_user_coder_through_csv_helper_function()
    {
        $csv = csv(['coders' => ['data' => TestCoder::class]])
            ->setHeader(['data'])
            ->append([
                $serialized = serialize(['name' => 'Colby'])
            ]);
        
        $csv->write($path = '/tmp/csv_helper_coder.csv');
        
        $this->assertEquals($serialized, csv($path)->first()->data);
    }
    
    /** @test */
    public function will_trim_whitespace()
    {
        $file = '/tmp/whitespace_test.csv';
        
        csv()->setHeader(['Name'])
            ->append(['   Colby   '])
            ->write($file);
        
        $this->assertEquals('Colby', csv()
            ->addCoder('Name', WhitespaceTrimmer::class)
            ->read($file)
            ->first()->Name);
    }
    
    /** @test */
    public function coders_work_with_aliases() // TODO: implement
    {
        $file = '/tmp/whitespace_test.csv';
        
        csv()->setHeader(['Name'])
            ->append(['   Colby   '])
            ->write($file);
        
        $this->assertEquals('Colby', csv(['aliases' => ['nm' => 'Name']])
            ->addCoder('nm', WhitespaceTrimmer::class)
            ->read($file)
            ->first()->nm);
    }
}

