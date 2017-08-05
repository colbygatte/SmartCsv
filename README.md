# Smart Csv
### File: `people.csv`
```
name,age,address,Favorite Type Of Food,favorite_color,other_info
Colby,26,1234 Country Lane,burgers,  Blue  ,
Mark,28,4321 City Street,ramen,  Red  ,
Derek,29,2314 Bayou Road,steak,  Purple  ,
```

# Creating a CSV
```php
<?php
csv(['name', 'age'])->append('Colby', 26)
    ->append('Mark', 28)
    ->write('info.csv');
```

# Reading a CSV
