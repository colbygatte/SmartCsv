<?php

namespace Tests\UnitTests;

use PHPUnit\Framework\TestCase;

class OnlyAndExcludeTest extends TestCase
{
    /** @test */
    public function can_use_load_only_column_feature()
    {
        $csv = csv(['save' => false])
            ->read(SAMPLE_CSV)
            ->only(['name', 'value 1']);

        // $csv->write('../../__delete.csv');

        $this->assertEquals(['name', 'value 1'], array_keys($csv->next()
            ->toArray(true)));
    }

    /** @test */
    public function test()
    {
        csv()->setHeader(['hello'])->append(['what'])->first()->hi;
    }
}