<?php

define('SAMPLE_CSV', __DIR__ . '/sample2.csv');

/**
 * @return \Faker\Generator
 */
function faker()
{
    static $faker;

    if (! isset($faker)) {
        $faker = \Faker\Factory::create();
    }

    return $faker;
}

/**
 * The point of this is to create a text csv and nothing else.
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

    $foods = array('pizza', 'sushi', 'pho', 'cheeseburgers', 'chicken', 'peanut butter', 'turkey', 'spaghetti', 'grilled chicken caesar salad');

    $attributePossibilities = array(
        array('hair color', function () { return faker()->colorName; }),
        array('favorite food', function () use ($foods) { return $foods[array_rand($foods)]; }),
        array('favorite vacation spot', function () { return faker()->city; }),
        array('height', function () { return faker()->numberBetween(4, 7) . 'ft ' . faker()->numberBetween(0, 11) . 'in'; }),
        array('weight', function () { return faker()->numberBetween(100, 250) . 'ln'; })
    );

    $header = array(
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
    );

    $csv = csv()->setHeader($header);

    for ($i = 0; $i < $rows; $i++) {
        $rowData = array(
            faker()->name,

            // age
            faker()->numberBetween(20, 100),

            // 3x contact fields
            $valOrEmpty('email'),
            $valOrEmpty('email'),
            $valOrEmpty('email'),
        );

        $copy = $attributePossibilities;
        shuffle($copy);

        // 3x attribute, value, notes
        for ($j = 0; $j < 3; $j++) {
            if (! faker()->boolean()) {
                array_push($rowData, '', '', '');
            }

            $randAttributeMaker = array_pop($copy);

            array_push($rowData, $randAttributeMaker[0], $randAttributeMaker[1](), $valOrEmpty('sentence'));
        }

        // random info
        array_push($rowData, serialize(array('random-string' => faker()->randomAscii)));

        $csv->appendRow($rowData);
    }

    $csv->write($writeTo);

    return $writeTo;
}

/**
 * @param string $path
 *
 * @return \ColbyGatte\SmartCsv\Csv
 */
function quick_csv_ages($path = '/tmp/smart-csv-dummy.csv')
{
    $csv = csv(array(
        array('name', 'age'), array('Colby', '25'), array('Sarah', '22')
    ));

    $csv->write($path);

    return $csv;
}


/**
 * @return \ColbyGatte\SmartCsv\Csv
 */
function sample_csv()
{
    return csv(SAMPLE_CSV);
}