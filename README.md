# PHP Db Seeder
Have your database populated with fake data in no time! 

* Very easy to use!
* Use time-tested fzaninotto/faker generators
* Easily map generators to database columns
* Supports related data
* Two ways of seeding a table: generators or plain array
* Ability to use your own generators
* Friendly autocomplete in modern IDE's (tested in phpstorm)


## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require tebazil/db-seeder
```

or add

```
"tebazil/db-seeder": "*"
```

to the `require` section of your `composer.json` file.

## Quick start

You can quickly seed your database with generated data with something like this:

```php
$pdo = new PDO('mysql:localhost', 'root', 'test'); // or new Pdo("mysql:host=" . $_ENV["MYSQL_HOST"],$_ENV["MYSQL_USER"],$_ENV["MYSQL_PASSWORD"])
$seeder = new \tebazil\dbseeder\Seeder($pdo);
$generator = $seeder->getGeneratorConfigurator();
$faker = $generator->getFakerConfigurator();

$seeder->table('article')->columns([
    'id', //automatic pk
    'book_id', //automatic fk
    'name'=>$faker->firstName,
    'content'=>$faker->text
        ])->rowQuantity(30);


$seeder->table('book')->columns([
    'id',
    'name'=>$faker->text(20),
])->rowQuantity(30);

$seeder->table('category')->columns([
    'id',
    'book_id',
    'name'=>$faker->text(20),
    'type'=>$faker->randomElement(['shop','cv','test']),
])->rowQuantity(30);

$seeder->refill();
```

!!! Caution! `$seeder->refill();` truncates all the tables specified and fills them with random data using the configuration provided. Sensible data might be deleted if you operate on producton/sensible database. Know what you are doing.

Let's see what happens here. First, you are creating a pdo connection, and initializing new `$seeder` object with it. Then you create a generator and faker wrappers instances to benefit from autocompletion. Then you configure each table for seeder. Then you ask to refill all configured tables. That's it.

## Filling table from generators

Each column is configured with appropriate generator. Generators can be of 5 types:
1) PK. Self incrementing PK (No support for composite keys yet)
2) Relation. Relation to some table column's value
3) Any faker generator
4) Anonymous function
5) Scalar value

1) PK. Table's pk is `id`, just leave `id`. It will be treated as PK automatically. If it isn't `id`, you'll have to use full syntax to configure PK

```php
...
'book_id'=> $generator->pk
...
```

2) Relation. Currently relation is when you fill some column randomly from some column that is in the other or current table. 
If your relation is exactly `some_table_name_id` and that table name is also being configured, you can leave just as it is. Correct relation will be autodetected and set automatically. If not, you will have to use full syntax. Here is the example for setting column parent_id from table `book` being populated from `book_category`.id column:

```php
...
'parent_id'=>$generator->relation('book_category', 'id'),
...
```

3) Faker generators. If fact it is very easy to use them. Just make sure you use `$faker = \tebazil\dbseeder\FakerWrapper()` instead of real faker instance when configuring columns. Call faker methods (with our without params) and attributes, as you would normally call them from faker, use `unique` and `optional` prefixes and benefit from autocompletion for the default locale - `FakerWrapper` can do all that. See [How it works] section to know more on why we are not using Faker instance here.
Examples:

```php
...
'first_name'=>$faker->firstName,
'preview'=>$faker->text(20),
'content'=>$faker->text,
'type'=>$faker->randomElement(['shop','cv','test']),
...
```

4) Anonymous functions or other php callables.
Anonymous function just has to return some scalar value that can be written to the appropriate database column. You can use them to do some precise random choice generators, for example.
Obvious example would be:

```php
'user_id'=>function() {
  return rand(1, 234343);
}
```

5) Plain value.
Same value is used for this field for each row.
 
```php
'is_active'=>1
```

Note: you cannot use these values as plain value types:
* Any php callable string (they will be called instead)
* 'pk'
* 'relation'
* 'faker'

## Filling table from array
You can also fill the table directly from array.
 
For arrays with numeric keys you have to explicitly define the corresponding column names. If you do not want to use some column, just use false/null for column configuration. If you do not configure some array columns, they will not be used.
```php
$array =
 [
    [1,'twinsen','the heir'],
    [2,'zoe', 'self-occupied'],
    [3, 'baldino', 'twinsunian elephant']
 ];
 $columnConfig = [false,'name','occupation'];
 
$seeder->table('users')->data($array, $columnConfig)->rowQuantity(30);
```

For arrays with keys corresponding to table columns you can omit the column configuration.

## Note on rowQuantity
You can set the desired row quantity for each table. If not set the default value(30) will be used. For filling from array the logic is this: if you don't define row quantity, all array will be filled. If you use the value greater than quantity of "lines" in the array provided, given array will be iterated over to fill as much lines as it is needed. If you provide the value lesser than array's number of lines, that number of lines will be used.

## How it works
When you issue commands like this 

```php
$seeder->table('category')->columns([
    'id',
    'book_id',
    'name'=>$faker->text(20),
    'type'=>$faker->randomElement(['shop','cv','test']),
])->rowQuantity(30);
```

nothing is done to your database. When `refill()` method is called, it first fills the tables that are not dependent on others. Then it iterates over to fill other (dependent) tables.
 
 
## Q&A
Q: Why do you use faker wrapper instead of faker instance? 
A: Short answer: to provide a shorter and neater syntax. Long answer: Faker generators provides the fake data immediately when you call it. We need that data to be available for each field we fill, not just one time. So, we can use anonymous function to fetch some data result from faker each time we need a different value like this:

```php
'first_name'=>function() use($faker) { return $faker->firstName; }
```
(`$faker` is a real faker instance)
 
Or we can use much cleaner syntax if we use `FakerWrapper` here: 

```php
'first_name'=>$faker->firstName
```
(`$faker` is a wrapper)

