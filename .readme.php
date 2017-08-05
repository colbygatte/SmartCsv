
# Creating a CSV
<?php

$csv = csv(['name', 'age']);
$csv->append('Colby', 26)
    ->append('Mark', 28);

?>

# Reading a CSV
# `people.csv`
```
name,age,address,Favorite Type Of Food,favorite_color,other_info
Colby,26,1234 Country Lane,burgers,  Blue  ,
Mark,28,4321 City Street,ramen,  Red  ,
Derek,29,2314 Bayou Road,steak,  Purple  ,
```
