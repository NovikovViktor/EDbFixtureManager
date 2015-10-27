EDbFixtureManager v.1.0.1
==================================
EDbFixtureManager - is simple tool for manage and use fixtures with Yii framework.

### Additions:

1) All attributes what you want to assign to certain model instance,
must be defined with `safe` validation rule;
2) Don't forget configure `tablePrefix` option for `db` connection definition;


### Basic usage:
1) Download the extension and place it in `extensions` directory;

2) Create file called fixtures.php, with content, what will be looks like this:
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
NOTE. The fields that you want to assign to the model instance,
need to be defined with `safe` validator in `rules()` method of the model!

3) Make sure that you configure database config for console application:
Add the following code to your console config:

``` php
...
'commandMap' => array(
        'fixtures' => array(
            'class'          => 'ext.fixture_manager.EDbFixtureManager', // import class of console command
            'pathToFixtures' => '/var/www/chooseone/protected/extensions/fixture_manager/fixtures.php', // pass the path to your fixtures file
            'modelsFolder'   => 'application.models.*', // specify the folder where your models classes lays
        ),
),
...
```

NOTE. If you you have a module architecture and your models lays in multiple folder you can specify `modelsFolder` as array.
E.g. ` 'modelsFolder'   => array('application.models.*', 'application.modules.user.models.*') ,`

4) Run in cli: `php path/to/yiic fixtures load` ;

### Advanced usage:

If you want to truncate your tables instead of just delete rows from database:

  A) Download PHPSQLParser (you may download it from here, but I RECOMEND to use the attached version of library) ;
  B) Place the extension folder where ever in you application, but don`t forget to configurate settings in console config,
     by appending:

``` php
 'commandMap' => array(
     'fixtures' => array(
     ...
     'php_sql_parser' => '/path/to/PHPSQLParser.php',
```
  C) Run cli command with option: `php protected/yiic fixtures load --truncateMode=true` ;

TO DO list
===============
1) Add developer friendly messages about non-existing models or tables

2) Add relation support

(OPTIONAL. If I find a better way to truncate DB, I will do this)
