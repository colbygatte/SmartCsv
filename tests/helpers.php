<?php

define('SAMPLE_CSV', __DIR__ . '/sample.csv');

/**
 * @param string $path
 *
 * @return \ColbyGatte\SmartCsv\Csv
 */
function quick_csv_ages($path = '/tmp/smart-csv-dummy.csv')
{
    $csv = csv(array(
        array('name', 'age'),
        array('Colby', '25'),
        array('Sarah', '22')
    ));

    $csv->write($path);

    return $csv;
}

/**
 * @return \ColbyGatte\SmartCsv\Csv
 */
function make_csv_complex_data()
{
    $csv = csv(array(
        array('name', 'age', 'favorite_foods'),
        array('Colby', '25', array('pho', 'cheeseburgers')),
        array('Sarah', '22', array('chips & salsa', 'coffee'))
    ));

    return $csv;
}

/**
 * @return \ColbyGatte\SmartCsv\Csv
 */
function sample_csv()
{
    return csv(SAMPLE_CSV);
}