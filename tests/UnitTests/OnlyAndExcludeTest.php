<?php

namespace Tests\UnitTests;

use ColbyGatte\SmartCsv\Csv\Sip;
use PHPUnit\Framework\TestCase;

class OnlyAndExcludeTest extends TestCase
{
    /** @test */
    public function can_use_load_only_column_feature()
    {
        $csv = (new Sip)->setSourceFile(SAMPLE_CSV)->only(['name', 'value 1']);

        $this->assertEquals(
            ['name', 'value 1'],
            array_keys($csv->getRows()[0]->toArray())
        );
    }

    /** @test */
    public function only_feature_will_throw_if_columns_not_set()
    {
        $message = thrown_message(function () {
            $csv = (new Sip)->only(['name']);
        });

        $this->assertEquals('Columns have not been set', $message);
    }
}
