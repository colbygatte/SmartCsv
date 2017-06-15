<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Coders\CoderInterface;
use ColbyGatte\SmartCsv\Coders\Trimmer;
use PHPUnit\Framework\TestCase;

class TestCoder implements CoderInterface
{
    public static function encode($data)
    {
        return serialize($data);
    }

    public static function decode($data)
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
        sample_csv()
            ->addCoder('name', TestCoder::class)
            ->write($path = '/tmp/dummy_csv.csv');

        $this->assertEquals(serialize('Prof. Adrian Schmeler IV'), csv($path)->first()->name);
    }

    /** @test */
    public function can_use_decoder()
    {
        $path = '/tmp/dummy_csv.csv';

        sample_csv()
            ->addCoder('name', TestCoder::class)
            ->write($path);

        $this->assertEquals('Prof. Adrian Schmeler IV', csv()
            ->addCoder('name', TestCoder::class)
            ->read($path)
            ->first()->name);
    }

    /** @test */
    public function cannot_use_invalid_coder()
    {
        $message = get_thrown_message(function () {
            sample_csv()->addCoder('name', InvalidCoder::class);
        });

        $this->assertEquals('Tests\UnitTests\InvalidCoder does not implement CoderInterface.', $message);
    }

    /** @test */
    public function can_user_coder_through_csv_helper_function()
    {
        $path = '/tmp/csv_helper_coder.csv';
        $data = ['name' => 'Colby'];
        $serialized = serialize($data);

        $csv = csv([
            'coders' => [
                'data' => TestCoder::class
            ]
        ]);

        $csv->setHeader(['data'])
            ->append([$serialized])
            ->write('/tmp/csv_helper_coder.csv');

        $this->assertEquals($serialized, csv($path)->first()->data);
    }

    /** @test */
    public function will_trim_whitespace()
    {
        $file = '/tmp/whitespace_test.csv';

        csv()
            ->setHeader(['Name'])
            ->append(['   Colby   '])
            ->write($file);

        $this->assertEquals(
            'Colby',
            csv()->addCoder('Name', Trimmer::class)
                ->read($file)
                ->first()->Name
        );
    }

    /** @test */
    public function coders_work_with_aliases() // TODO: implement
    {
        $file = '/tmp/whitespace_test.csv';

        csv()
            ->setHeader(['Name'])
            ->append(['   Colby   '])
            ->write($file);

        $this->assertEquals(
            'Colby',
            csv(['aliases' => ['nm' => 'Name']])->addCoder('nm', Trimmer::class)
                ->read($file)
                ->first()->nm
        );
    }
}

