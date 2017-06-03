# SmartCsv

## Usage
The main two classes here are Csv and Row.
A helper function `csv()` is provided for elegant syntax. The first parameter can be either a string or an array. If a string is given, it will automatically be read. If an array is given, the object will populate itself with the data, using the first value as the header.

## Examples
### Example
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

### Example: Line by line
If you need to make changes, use Csv::iterate(). It will pass each $row to a function. All changes will be saved to a new file.
```php
use ColbyGatte\SmartCsv\Csv;

quick_csv_ages($path = '/tmp/iterate.csv');

Csv::iterate($path, $savePath = '/tmp/iterated.csv', function ($row) {
    $row->name = 'NOBODY';
});

echo csv($savePath)->first()->name;
```
__Output__
```
NOBODY
```

### Example
```php
<?php
// Read CSV, make changes, save
foreach ($csv = csv('/tmp/names.csv') as $row) {
    $row->name = strtoupper($row->name);
}

$csv->write('/tmp/uppercase_names.csv');

foreach (csv('/tmp/uppercase_names.csv') as $row) {
    echo "{$row->name} is {$row->age}! \n";
}
```
__Output__
```        
COLBY is 25!
TAMMY is 40!
EVAN is 22!
```
### Example
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

### Example
__Index Aliases__
```php
<?php
$csv = csv(
    [
        ['A Really Long String Of Text'],
        ['I LOVE PHP'],
        ['WOOOOOOOOO']
    ],
    // Define the alias
    ['shortstring' => 'A Really Long String Of Text']
);

$csv->each(function ($row) {
   $row->shortstring = strtolower($row->shortstring); 
});

// Index aliases can also be used in place of the original column name when writing
$csv->useAliases()->write('/tmp/using_aliases.csv');

echo csv('/tmp/using_aliases.csv')->first()->shortstring;
```
__Output__
```
i love php
```

### Example
__Coders__

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

### Example
__Filters__
```php
<?php
$csv = csv([
    ['text'],
    ['hello'],
    ['sup']
]);

$csv->addFilter('text', new class implements FilterInterface {
    public static function filter($data)
    {
        return strtoupper($data);
    }
});

$csv->first()->text; // HELLO
```

### For more...
Check out the tests in the tests/ directory to see more examples.
