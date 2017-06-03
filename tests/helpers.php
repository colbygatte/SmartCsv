<?php

/**
 * @param string $path
 *
 * @return \ColbyGatte\SmartCsv\Csv
 */
function quick_csv_ages($path = '/tmp/smart-csv-dummy.csv')
{
    $csv = csv();
    $csv->setHeader(['name', 'age']);
    $csv->appendRow(['Colby', '25']);
    $csv->appendRow(['Sarah', '22']);
    $csv->write($path);

    return $csv;
}

/**
 * @return \ColbyGatte\SmartCsv\Csv
 */
function make_csv_complex_data()
{
    $csv = csv();
    $csv->setHeader(['name', 'age', 'favorite_foods']);
    $csv->appendRow(['Colby', '25', ['pho', 'cheeseburgers']]);
    $csv->appendRow(['Sarah', '22', ['chips & salsa', 'coffee']]);

    return $csv;
}