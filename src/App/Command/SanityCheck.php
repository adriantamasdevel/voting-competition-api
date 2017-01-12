<?php

namespace App\Command;


use Knp\Command\Command;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class SanityCheck extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription("Checks whether the MySQL server is configured correctly for utf8_mb4.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var $conn Connection */
        $conn = $app[Connection::class];

        $statement = $conn->ping();

        $statement = $conn->query("SELECT *, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
FROM INFORMATION_SCHEMA.SCHEMATA where SCHEMA_NAME like 'imagecompetition'; ");

        $result = $statement->fetchAll();
        var_dump($result);

//        $statement = $conn->query("SELECT @@GLOBAL.sql_mode;");
//        $result = $statement->fetchAll();
//        var_dump($result);

        // http://www.fileformat.info/info/unicode/char/4e01/index.htm
        // 'male adult; robust, vigorous; 4th heavenly stem'

        $mb2Char = "\xC2\xB6";
        $query = "select LENGTH('$mb2Char') as length, char_length('$mb2Char') as char_length;";
        $statement = $conn->query($query);
        $rows = $statement->fetchAll();
        echo "mb2Char: \n";
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                echo " $key => $value, ";
            }
            echo "\n";
        }

        $mb3Char = "\xE4\xB8\x81";
        $query = "select LENGTH('$mb3Char') as length, char_length('$mb3Char') as char_length;";
        $statement = $conn->query($query);
        $rows = $statement->fetchAll();
        echo "mb3Char: \n";
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                echo " $key => $value, ";
            }
            echo "\n";
        }

        $mb4Char = "\xF0\x9F\x98\x89";
        $query = "select LENGTH('$mb4Char') as length, char_length('$mb4Char') as char_length;";
        $statement = $conn->query($query);
        $rows = $statement->fetchAll();
        echo "mb4Char: \n";
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                echo " $key => $value, ";
            }
            echo "\n";
        }

        // SHOW [GLOBAL | SESSION] VARIABLES
        $statement = $conn->query("SHOW GLOBAL VARIABLES where variable_name like '%collation%' or variable_name like '%character%'");
        $rows = $statement->fetchAll();
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                echo " $key => $value, ";
            }
            echo "\n";
        }

        echo "Ok\n";
    }
}
