[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/NovikovViktor/EDbFixtureManager/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/NovikovViktor/EDbFixtureManager/?branch=master) [![Maintainability](https://api.codeclimate.com/v1/badges/96bfb98dce5f5404d571/maintainability)](https://codeclimate.com/github/NovikovViktor/EDbFixtureManager/maintainability)

EDbFixtureManager v1.0
==================================
Is a tool which provides an ability to use fixtures with Yii framework.

### Note:

1) All attributes you want to fill via fixtures data, must be defined with `safe` validation rule (`rules()` method);
2) Don't forget configure `tablePrefix` option for `db` connection definition;
3) Your tables will be purged when you loading fixtures.

### Basic usage:
1) Download extension and place it in `extensions` directory;

2) Create file `fixtures.php`, with content, what will be looks like this:
``` php
<?php
return array(
    'ModelClassName' => array(
        'modelInstanceId' => array(
            'field1' => 'value1',
            'field2' => 'value2',
            ...
        ),
        ...
    ),
    ...
);
```

3) Make sure that you have configured database for console application.
Add the following code to your console config:

``` php
...
'commandMap' => array(
        'fixtures' => array(
            'class'          => 'ext.fixture_manager.EDbFixtureManager', // import class of console command
            'pathToFixtures' => '/path/to/fixtures.php', // pass the path to your fixtures file
            'modelsFolder'   => 'application.models.*', // specify the folder where your models classes lays
        ),
),
...
```

NOTE. If you have a multiple models folder you can specify `modelsFolder` as an array.
E.g. ` 'modelsFolder'   => array('application.models.*', 'application.modules.user.models.*') ,`

4) Run command: `php path/to/yiic fixtures load` ;

### Advanced usage:

If you want to truncate your tables instead of deleting rows from database:

  A) Download PHPSQLParser (you may download it from [here](https://code.google.com/archive/p/php-sql-parser));
  B) Place the extension folder where ever in you application, but don`t forget to configure settings in console config:

``` php
 'commandMap' => array(
     'fixtures' => array(
     ...
     'php_sql_parser' => '/path/to/PHPSQLParser.php',
```
  C) Run cli command with option: `php protected/yiic fixtures load --truncateMode=true` ;

TO DO list
===============
1) Add developer friendly messages about non-existing models or tables;

2) Add relation support;

3) Find a better way to truncate the database.__
