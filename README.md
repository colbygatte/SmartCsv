# SmartCsv

## Usage
The main two classes here are Csv and Row.
A helper function `csv()` is provided for elegant syntax. The first parameter can be either a string or an array. If a string is given, it will automatically be read. If an array is given, the object will populate itself with the data, using the first value as the header.

## Examples
### Create a CSV, then read it
```php
<?php
// Create CSV
$csv = csv([
    ['name', 'age'],
    ['Colby', '25'],
    ['Tammy', '40'],
    ['Evan', '22']
]);

$csv->write('/tmp/names.csv');

// Read CSV
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

### Line by line
The csv() helper function can take an array of options as the first parameter.
If the save option is set to false, the rows are not saved. The CSV can only be iterated over once (in a single instance).
```
foreach(csv(['file' => '/tmp/some_csv.csv', 'save' => false]) as $row) {
    
}
```

If you need to make changes, pass 'alter' option with the location of the file. It will pass each $row to a function. All changes will be saved to a new file.
Rows can also be deleted.
```php
$path = '/tmp/iterate.csv';

// make dummy csv
csv([
    ['name', 'age'],
    ['Colby', '26'],
    ['Sarah', '22'],
    ['Ben', '50']
])->write($path);

// now we are going to alter the dummy csv, but save the altered version to a new location
$alterFile = '/tmp/altered.csv';

$options = ['file' => $path, 'alter' => $alterFile];

foreach (csv($options) as $row) {
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
$csv = csv([
    ['name', 'age'],
    ['Frankenstein', '26'],
    ['Sarah', '22'],
    ['Ben', '50']
]);

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

### Filtering
```
$path = '/tmp/dummy.csv';

$csv = csv([
    ['name', 'age'],
    ['Colby', '26'],
    ['Sarah', '22'],
    ['Ben', '50']
]);

$csv->addFilter(function ($row) {
        if ($row->name == 'Colby') {
            $row->name = strtoupper($row->name);
        }
    })
    ->addFilter(function ($row) {
        if ($row->name == 'Sarah') {
            $row->age = 102510;
        }
    })
    ->addFilter(function ($row) {
        if ($row->name == 'Ben') {
            $row->delete();
        }
    });

$csv->runFilters()
    ->write($path);

print_r(file_get_contents($path));
```
__Output__
```
name,age
COLBY,26
Sarah,102510
```

### Grouping
When you have multiple columns that you need to bring together, you can use groupColumns().
```php
<?php
// Grouping data
$csv = csv([
    ['Spec 1', 'Val 1', 'UOM 1', 'Spec 2', 'Val 2', 'UOM 2'],
    ['Height', '21', 'in', 'Weight', '30', 'lb']
]);

$grouped = $csv->first()->groupColumns('Spec', ['Val', 'UOM']);

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
$csv = csv(
    [
        ['A Really Long Column Name'],
        ['I LOVE PHP'],
        ['WOOOOOOOOO']
    ],
    // Define the alias
    ['shortname' => 'A Really Long Column Name']
);

$csv->each(function ($row) {
   $row->shortname = strtolower($row->shortname); 
});

// Index aliases can also be used in place of the original column name when writing
$csv->useAliases()->write('/tmp/using_aliases.csv');

echo csv('/tmp/using_aliases.csv')->first()->shortname;
```
__Output__
```
i love php
```

### Coders
If you want to store serialized data in a column, you can use Coders/Serialize.
Custom coders can be defined, and must implement the Coders/CoderInterface.
__IMPORTANT:__ Coders are ran when reading a CSV and when writing it, NOT when accessing individual values. You must add a coder to an instance of CSV before reading!
```php
<?php
use ColbyGatte\SmartCsv\Coders\Serialize;

$csv = csv([
    ['array'],
    [['oh', 'my', 'goodness']]
]);

// First parameter is the column to use the coder on
$csv->addCoder('array', Serialize::class);
$csv->write('/tmp/serialized_row.csv');

// Remember: Add coders before reading
print_r(csv('/tmp/serialized_row.csv')->first()->array);

print_r(csv()->addCoder('array', Serialize::class)->read('/tmp/serialized_row.csv')->first()->array);
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

