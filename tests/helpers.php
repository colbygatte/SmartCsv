<?php

define('SAMPLE_CSV', __DIR__ . '/sample.csv');

/**
 * @return \Faker\Generator
 */
function faker()
{
    static $faker;

    return $faker ?: $faker = \Faker\Factory::create();
}

/**
 * A generic CSV that all features of this library can be tested on is created.
 *
 * @param     $writeTo
 * @param int $rows
 *
 * @return \ColbyGatte\SmartCsv\Csv
 */
function csv_faker($writeTo, $rows = 20)
{
    $valOrEmpty = function ($val) {
        return faker()->boolean() ? faker()->$val : '';
    };

    $foods = [
        'pizza',
        'sushi',
        'pho',
        'cheeseburgers',
        'chicken',
        'peanut butter',
        'turkey',
        'spaghetti',
        'grilled chicken caesar salad'
    ];

    $attributePossibilities = [
        'hair color' => function () {
            return faker()->colorName;
        },
        'favorite food' => function () use ($foods) {
            return $foods[array_rand($foods)];
        },
        'favorite vacation spot' => function () {
            return faker()->city;
        },
        'height' => function () {
            return faker()->numberBetween(4, 7) . 'ft ' . faker()->numberBetween(0, 11) . 'in';
        },
        'weight' => function () {
            return faker()->numberBetween(100, 250) . 'lb';
        }
    ];

    $header = [
        'name',
        'age',
        'contact 1',
        'contact 2',
        'contact 3',
        'attribute 1',
        'value 1',
        'notes 1',
        'attribute 2',
        'value 2',
        'notes 2',
        'attribute 3',
        'value 3',
        'notes 3',
        'other_info'
    ];

    $csv = csv()->setHeader($header);

    for ($i = 0; $i < $rows; $i++) {
        $rowData = [
            faker()->name,

            // age
            faker()->numberBetween(20, 100),

            // 3x contact fields
            $valOrEmpty('email'),
            $valOrEmpty('email'),
            $valOrEmpty('email'),
        ];

        $copy = $attributePossibilities;
        shuffle($copy);

        // 3x attribute, value, notes
        for ($j = 0; $j < 3; $j++) {
            if (! faker()->boolean()) {
                array_push($rowData, '', '', '');

                continue;
            }

            $randomAttribute = array_rand($attributePossibilities);

            array_push($rowData, $randomAttribute, $attributePossibilities[$randomAttribute](),
                $valOrEmpty('sentence'));
        }

        // random info
        array_push($rowData, serialize(['random-string' => faker()->randomAscii]));

        $csv->append($rowData);
    }

    $csv->write($writeTo);

    return $writeTo;
}

/**
 * @param $callable
 *
 * @return null|string
 */
function thrown_message($callable)
{
    try { $callable(); } catch (\Exception $e) { return $e->getMessage(); }
}