<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Coders\CoderInterface;
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
        quick_csv_ages($path = '/tmp/dummy_csv.csv')->addCoder('name', TestCoder::class)->write($path);

        $this->assertEquals(
            serialize('Colby'),
            csv($path)->first()->name
        );
    }

    /** @test */
    public function can_use_decoder()
    {
        quick_csv_ages($path = '/tmp/dummy_csv.csv')->addCoder('name', TestCoder::class)->write($path);

        $this->assertEquals(
            'Colby',
            csv()->addCoder('name', TestCoder::class)->read($path)->first()->name
        );
    }

    /** @test */
    public function cannot_use_invalid_coder()
    {
        $assert = false;

        try {
            quick_csv_ages($path = '/tmp/dummy_csv.csv')->addCoder('name', InvalidCoder::class);
        } catch (\Exception $e) {
            $assert = true;
        }

        $this->assertTrue($assert);
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
            ->appendRows([
                [$serialized]
            ])
            ->write('/tmp/csv_helper_coder.csv');

        $this->assertEquals(
            $serialized,
            csv($path)->first()->data
        );
    }
}

