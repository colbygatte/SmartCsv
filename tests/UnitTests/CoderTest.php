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
        sample_csv()->addCoder('name', TestCoder::class)->write($path = '/tmp/dummy_csv.csv');

        $this->assertEquals(
            serialize('Mrs. Emilie Pacocha Jr.'),
            csv($path)->first()->name
        );
    }

    /** @test */
    public function can_use_decoder()
    {
        $path = '/tmp/dummy_csv.csv';

        sample_csv()->addCoder('name', TestCoder::class)->write($path);

        $this->assertEquals(
            'Mrs. Emilie Pacocha Jr.',
            csv()->addCoder('name', TestCoder::class)->read($path)->first()->name
        );
    }

    /** @test */
    public function cannot_use_invalid_coder()
    {
        $assert = false;

        try {
            sample_csv()->addCoder('name', InvalidCoder::class);
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

