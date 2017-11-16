<?php

/**
 * EDbFixtureManager represent the console command which helps you to manage your basic fixtures.
 * Available command properties:
 *    pathToFixtures - path to your fixtures file
 *    modelsFolder   - path to folder where your models classes lay, default to `application.models.*`
 *
 * Note:
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

    /**
     * Load fixtures into database from fixtures file
     */
    public function actionLoad()
    {
        echo "\033[36m Are you sure you want to load fixtures? Your database will be purged! [Y/N] \033[0m";

        $phpStdin     = fopen("php://stdin", "r");
        $inputValue   = fgets($phpStdin);
        $purifiedLine = preg_replace('/[^A-Za-z0-9\-]/', '', $inputValue);

        if (strtolower($purifiedLine) == 'n') {
            echo "\033[34m Stopping the executing... Done. \033[0m \n";
            die;
        }

        if (empty($this->pathToFixtures) || !file_exists($this->pathToFixtures)) {
            echo "\033[33m There is no file with fixtures to load! Make sure that you create file with fixtures,
                  or pass correct file name \033[0m \n";
            die;
        }

        // import models classes to make available create new instances
        if (is_array($this->modelsFolder)) {
            foreach ($this->modelsFolder as $folder) {
                Yii::import($folder); //import each folder form array
            }
        } else {
            Yii::import(empty($this->modelsFolder) ? 'application.models.*' : $this->modelsFolder);
        }

        $fixtures  = require_once $this->pathToFixtures; // require that file with fixtures, will be array
        $errorList = array();

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
            foreach ($errorList as $errors) { // run over all errors and display error what occur during saving into db
                foreach ($errors as $error) {
                    foreach ($error as $value) {
                        echo "\033[37;41m" . $value . "\033[0m   \n"; //display error
                    }
                }
            }
            die;
        }

        echo "\033[37;42m All fixtures loaded properly \033[0m \n";
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
