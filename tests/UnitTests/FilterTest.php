<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Filters\FilterInterface;
use PHPUnit\Framework\TestCase;

class TestFilter implements FilterInterface
{
    public static function filter($data)
    {
        return strtoupper($data);
    }
}

class InvalidFilter
{
}

class FilterTest extends TestCase
{
    /** @test */
    public function can_use_filters()
    {
        $csv = quick_csv_ages($path = '/tmp/dummy_csv.csv')->addFilter('name', TestFilter::class);

        $this->assertEquals('COLBY', $csv->first()->name);
    }

    /** @test */
    public function cannot_use_invalid_filter()
    {
        $assert = false;

        try {
            quick_csv_ages($path = '/tmp/dummy_csv.csv')->addFilter('name', InvalidFilter::class);
        } catch (\Exception $e) {
            $assert = true;
        }

        $this->assertTrue($assert);
    }
}

