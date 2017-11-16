<?php

/**
 * EDbFixtureManager represent the console command which helps you to manage your basic fixtures.
 * Available command properties:
 *    pathToFixtures - path to your fixtures file
 *    modelsFolder   - path to folder where your models classes lay, default to `application.models.*`
 *    php_sql_parser - path to PHPSQLParser class file (optional)
 *
 * Additions:
 * 1) All attributes you want to fill via fixtures data, must be defined with `safe` validation rule (`rules()` method);
 * 2) Don't forget configure `tablePrefix` option for `db` connection definition;
 * 3) Your tables will be purged when you loading fixtures;
 *
 * For more complex info about usage, see README.md
 */
class EDbFixtureManager extends CConsoleCommand
{
    public $pathToFixtures;
    public $modelsFolder;
    public $php_sql_parser;

    /**
     * Load fixtures into database from fixtures file
     */
    public function actionLoad($truncateMode = false)
    {
        $startTime = microtime(true); // check time of start execution script
        $startMemory = round(memory_get_usage(true) / 1048576, 2);

        echo "\033[36m Are you sure you want to load fixtures? Your database will be purged! [Y/N] \033[0m";

        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        $purgedLine = preg_replace('/[^A-Za-z0-9\-]/', '', $line); // or trim($line)

        if (strtolower($purgedLine) == 'n') {
            echo "\033[34m Stopping the executing... Done. \033[0m \n";
            die;
        }

        // assign file variable what consist full path to fixtures file
        $file = empty($this->pathToFixtures) ? __DIR__ . '/fixtures.php' : $this->pathToFixtures;

        // import models classes to make available create new instances
        if (empty($this->modelsFolder)) { // if property empty default load form the models folder in protected directory
            Yii::import('application.models.*');
        } else {
            if (is_array($this->modelsFolder)) { // if modelsFolder defined check if it array of models
                foreach ($this->modelsFolder as $key => $folder) {
                    Yii::import($folder); //import each folder form array
                }
            } else {
                Yii::import($this->modelsFolder); //import single models folder
            }
        }

        if (!file_exists($file)) { // check if exist file with fixtures
            echo "\033[33m There is no file with fixtures to load! Make sure that you create file with fixtures,
                  or pass correct file name \033[0m \n";
            die;
        }

        $fixtures = require_once $file; // require that file with fixtures, will be array
        $errorList = array(); // create array what will consist model errors

        if ($truncateMode) { // if truncated mode ON
            echo "\033[34m Wait database truncating... \033[0m \n\n";
            require_once $this->php_sql_parser; // require sql parser
            $keys = array(); // prepare array for store FK`s data
            foreach (Yii::app()->db->schema->getTables() as $tableSchema) { // Run over all tables
                $parser = new PHPSQLParser();
                $result = Yii::app()->db->createCommand('SHOW CREATE TABLE ' . $tableSchema->name)->queryRow(); // getting create sql statement for current table
                $parsed = $parser->parse($result['Create Table']); //parse sql create statement
                $table = $parsed['TABLE']['base_expr']; // get table name
                foreach ($parsed['TABLE']['create-def']['sub_tree'] as $key => $column_def) { // run over all columns of the table
                    if ($column_def['expr_type'] != 'foreign-key') { // check if column is FOREIGN KEY
                        continue;
                    }
                    $fkName = $column_def['sub_tree'][0]['sub_tree']['base_expr']; // get FOREIGN KEY name
                    Yii::app()->db->createCommand()->dropForeignKey(str_replace('`', '', $fkName), str_replace('`', '', $table)); // frop FOREIGN KEY
                    $keys[] = array( // set FOREIGN KEY data to be recreated
                        'name'       => str_replace('`', '', $column_def['sub_tree'][0]['sub_tree']['base_expr']),
                        'table'      => str_replace('`', '', $table),
                        'column'     => $column_def['sub_tree'][3]['sub_tree'][0]['no_quotes'],
                        'ref_table'  => $column_def['sub_tree'][4]['sub_tree'][1]['no_quotes'],
                        'ref_column' => $column_def['sub_tree'][4]['sub_tree'][2]['sub_tree'][0]['no_quotes'],
                        'update'     => $column_def['sub_tree'][4]['sub_tree'][5]['base_expr'],
                        'delete'     => $column_def['sub_tree'][4]['sub_tree'][5]['base_expr'],
                    );
                }
            }
            foreach (Yii::app()->db->schema->getTables() as $tableSchema) { // after drop all FK`s truncate each table
                Yii::app()->db->createCommand()->truncateTable(str_replace('`', '', $tableSchema->name));
            }
            foreach ($keys as $identifier => $foreignKey) { // recreate all FK`s to make sure schema is safe
                Yii::app()->db->createCommand()->addForeignKey($foreignKey['name'],
                    $foreignKey['table'],
                    $foreignKey['column'],
                    $foreignKey['ref_table'],
                    $foreignKey['ref_column'],
                    $foreignKey['update'],
                    $foreignKey['delete']
                );
            }
        }

        foreach ($fixtures as $modelClass => $instances) { // run through the array with fixtures
            $modelClass::model()->deleteAll(); // removing old rows from database if database is not truncated
            foreach ($instances as $key => $instance) { // go through all instances for certain model, and save it into db
                $model = new $modelClass();
                $model->attributes = $instances[$key];
                if (!$model->save()) { // if model can't be saved append errors into error list array
                    $errorList[] = $model->getErrors();
                }
            }
        }

        if (!empty($errorList)) { // if error list not empty
            echo "\033[31m Validation errors occurs during loading the fixtures, some fixtures wasn't loaded to database \033[0m  \n
                    \033[33m  The next errors occur \033[0m \n";
            foreach ($errorList as $key => $errors) { // run over all errors and display error what occur during saving into db
                foreach ($errors as $k => $error) {
                    foreach ($error as $i => $value) {
                        echo "\033[37;41m" . $value . "\033[0m   \n"; //display error
                    }
                }
            }
            die;
        }

        echo "\033[37;42m All fixtures loaded properly \033[0m   \n
              Script execution time: " . round(microtime(true) - $startTime, 2) . " ms.  \n";

        $endMemory = round(memory_get_usage(true) / 1048576, 2);
        echo "Memory used: " . ($endMemory - $startMemory) . "mb \n";
    }

    /**
     * Show a some info about `fixtures` command
     *
     * @return string
     */
    public function getHelp()
    {
        $output = "\033[34m This command will allow you to manage your fixtures in a simple way.
 Be careful all rows from database will be removed! \033[0m \n\n";

        return $output . parent::getHelp();
    }
}
