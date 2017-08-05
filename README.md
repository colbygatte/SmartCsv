# Smart Csv
### File: `people.csv`
```csv
name,age,address,Favorite Type Of Food,attribute 1,value 1,note 1,attribute 2,value 2,note 2
Colby,26,1234 Country Lane,burgers,hair color,brown,nil,height,6,so tall
Mark,28,4321 City Street,ramen,shoe size,9,big foot!,height,5'9'',so short
Tammy,29,2314 Bayou Road,steak,pant size,4,nil,ring size,7,beautiful!
```

# Overview of helper functions
* `csv()` - Used for creating CSV's. Accepts one parameter: The header.
* `csv_sip()` - Read a CSV row-by-row. Accepts on parameter: The file path or file handle
* `csv_slurp()` - Read an entire CSV into memory. Accepts one parameter: The file path or file handle
* `csv_writer()`
* `csv_alter()`

# Basic usage
Use `csv()` for creating a new CSV
```php
<?php
$csv = csv(['name', 'age']);
$csv->append(['Mark', 28], ['Colby', 26])  // Multiple rows can be passed at once
    ->write('info.csv');
```

`csv_sip()` will read a CSV file row-by-row (not line by line - a single row of a CSV can span multiple lines)
```php
<?php
foreach (csv_sip('people.csv') as $row) {
    echo $row->name; 
}
```

`csv_slurp()` will read the entire CSV file at once
```php
<?php
$csv = csv_slurp('people.csv');
foreach ($csv as $row) {
    echo $row->age;
}
```

`csv_writer()` is for writing directly to a CSV file.
```php
<?php
$writer = csv_writer('data.csv', ['time', 'cost']);
$writer->append(['4pm', '$100'], ['1am', '$14']); // Append automatically writes each row
```

`csv_alter()` uses Sip to iterate over each column, and after the iteration, the column is written to a new csv. This allows for easy data manipulation without changing the source.
```php
<?php
$alter = csv_alter('people.csv', 'altered-people.csv');
foreach ($alter as $row) {
    // Change stuff
    $row->name = strtoupper($row->name);
    // If the delete() method is called on the row, it will not be included in the new CSV
    $row->delete();
}
```

# Column Grouping
Column grouping works by matching header endings. Example, `attribute 1` and `value 1` are matched because they both end with the same thing, which is `' 1'`. It is not matching the numbers, but the entire remaining string after `attribute` and `value`.
```php
<?php
$csv = csv_sip('people.csv')->makeGroup('attributes', 'attribute', ['value', 'note']);
foreach ($csv as $row) {
    print_r($row->groups()->attributes);
}
```
Output (for first value):
```
Array
(
    [0] => Array
        (
            [attribute] => hair color
            [value] => brown
            [note] => nil
        )

    [1] => Array
        (
            [attribute] => height
            [value] => 6
            [note] => so tall
        )

)
```