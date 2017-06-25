# SmartCsv

I wrote SmartCsv while working on a project that required handling multiple CSVs in several different formats.

This tool has been a huge time saver for me, and has made my code much more readable.

The Csv functions in 3 main modes: slurp and sip. Once a mode is set, it cannot be changed.

* __Slurp__: read the entire CSV into memory. This mode is default.
* __Sip__: intended for line-by-line manipulation.
* __Alter__: uses sip mode, but after iterating over reach row, the changes are saved to a new file.

Other feature highlights:
* __Magic properties__: Access values through magic properties (corresponding to column name, or index aliases)
* __Index aliases__: Say you have column 'Total Amount Of Lions', instead of using `$row->{'Total Amount Of Lions'}`, you can use an index alias and use `$row->total_lions` instead! 
* __Column grouping__: Say you have `Attribute 1`, `Value 1`, `Attribute 2`, `Value 2`, etc... Use column grouping for easy parsing (see below)
* __Multi-CSV search__: Find rows in a CSV based on values in an instance of Csv.
## For the impatient

SmartCsv provides 4 helper functions:
 * `csv()`
 * `csv_slurp()`
 * `csv_sip()`
 * `csv_alter()`
 * `csv_search()`

`csv_slurp()`, `csv_sip()`, and `csv_alter()` all use `csv()`.

Install with composer:
```
compose require colbygatte/smart-csv
```

__File: prices.csv__
```$xslt
amount,currency
10,dollars
50,euros
```

__Slurp__
```php
<?php
foreach (csv_slurp('prices.csv') as $row) {
    echo $row->currency;
}
```

__Sip__
```php
<?php
foreach (csv_sip('prices.csv') as $row) {
    echo $row->currency;
}
```

__Alter__
```php
<?php
foreach (csv_alter('prices.csv', 'altered_prices.csv') as $row) {
    $row->amount = $row->amount * 10;
}
```

## Search
Searching can be done in sip or slurp mode.
```php
<?php
// Returns new Csv instance
$results = csv_search(csv_sip('prices.csv'), [
    function ($row) {
        return $row->amount > 50;
    }
]);

$results->write('results.csv');
```

## Advanced usage
The main two classes here are Csv and Row.
A helper function `csv()` is provided for elegant syntax. The first parameter can be either a string or an array. If a string is given, it will automatically be read. If an array is given, the object will populate itself with the data, using the first value as the header.

## Examples
### Create a CSV, then read it
```php
<?php
// Create CSV
csv()->header(['name', 'age'])
    // Each parameter passed to append must be an array, representing a row in the CSV.
    ->append(
        ['Colby', '25'],
        ['Tammy', '40'],
        ['Evan', '22']
    )
    ->write('/tmp/names.csv');

// Read CSV. csv() defaults to slurp mode, so here the whole file will be loaded before the loop starts.
foreach (csv('/tmp/names.csv') as $row) {
    echo "{$row->name} is {$row->age}! \n";
}
```
__Output__
```
Colby is 25!
Tammy is 40!
Evan is 22!
```

### Weird alter example
Here is an example of alter mode without using the `csv_alter()` helper function (and using the `csv()` helper function only).
Rows can also be deleted.
```php
<?php
$path = '/tmp/iterate.csv';

// make dummy csv
csv()->header(['name', 'age'])
    ->append(
        ['name', 'age'],
        ['Colby', '26'],
        ['Sarah', '22'],
        ['Ben', '50']
    )
    ->write($path);

// now we are going to alter the dummy csv, but save the altered version to a new location
$alterFile = '/tmp/altered.csv';

foreach (csv_alter($path, $alterFile) as $row) {
    if ($row->name == 'Colby') {
        $row->name = strtoupper($row->name);
    }

    if ($row->name == 'Sarah') {
        $row->age = 102510;
    }

    if ($row->name == 'Ben') {
        $row->delete();
    }
}

print_r(file_get_contents($alterFile));
```
__Output__
```
name,age
COLBY,26
Sarah,102510
```

### Searching
```php
$csv = csv()->header(['name', 'age'])
    ->append(
        ['name', 'age'],
        ['Frankenstein', '26'],
        ['Sarah', '22'],
        ['Ben', '50']
);

$resultCsv = csv_search($csv, [
    function ($row) {
        return $row->age < 30;
    },
    function ($row) {
        return strlen($row->name) < 6;
    }
]);

$resultCsv->write('/tmp/results.csv');

print_r(file_get_contents('/tmp/results.csv'));
```
__Output__
```        
name,age
Sarah,22
```

### Grouping
When you have multiple columns that you need to bring together, you can use groupColumns().
```php
<?php
// Grouping data
$csv = csv()->header(['Spec 1', 'Val 1', 'UOM 1', 'Spec 2', 'Val 2', 'UOM 2'])
    ->append(
        ['Height', '21', 'in', 'Weight', '30', 'lb']
    );

$csv->columnGroup('spec', 'Spec', ['Val', 'UOM']);

$grouped = $csv->first()->groups()->spec;

print_r($grouped);
```

```
(
    [0] => Array
        (
            [Spec] => Height
            [Val] => 21
            [UOM] => in
        )
    [1] => Array
        (
            [Spec] => Weight
            [Val] => 30
            [UOM] => lb
        )
)
```

### Index Aliases
```php
<?php
$csv = csv(['aliases' => ['shortname' => 'A Really Long Column Name']])
    ->header(['A Really Long Column Name'])
    ->append(
        ['I LOVE PHP'],
        ['WOOOOOOOOO']
    );

$csv->each(function ($row) {
   $row->shortname = strtolower($row->shortname); 
});

// Index aliases can also be used in place of the original column name when writing
$csv->useAliases()->write('/tmp/using_aliases.csv');
echo file_get_contents('/tmp/using_aliases.csv');

```
__Output__
```
shortname
i love php
wooooooooo
```

### Coders
If you want to store serialized data in a column, you can use Coders/Serialize.
Custom coders can be defined, and must implement the Coders/CoderInterface.
__IMPORTANT:__ Coders are ran when reading a CSV and when writing it, NOT when accessing individual values. You must add a coder to an instance of CSV before reading!
```php
<?php
use ColbyGatte\SmartCsv\Coders\Serialize;

$csv = csv()->header(['some_column_title'])
    ->append(
        [['oh', 'my', 'goodness']]
    );

// First parameter is the column to use the coder on
$csv->addCoder('some_column_title', Serialize::class);
$csv->write('/tmp/serialized_row.csv');

// Remember: Add coders before reading
print_r(csv('/tmp/serialized_row.csv')->first()->some_column_title);

print_r(csv()->addCoder('some_column_title', Serialize::class)->read('/tmp/serialized_row.csv')->first()->some_column_title);
```
__Output__
```
a:3:{i:0;s:2:"oh";i:1;s:2:"my";i:2;s:8:"goodness";}

Array
(
    [0] => oh
    [1] => my
    [2] => goodness
)
```

