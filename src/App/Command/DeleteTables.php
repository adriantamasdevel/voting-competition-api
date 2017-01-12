<?php

namespace App\Command;

use Knp\Command\Command;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteTables extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription("Deletes all current DB tables.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var $conn Connection */
        $conn = $app[Connection::class];

        $conn->exec("drop table IF EXISTS vote ");
        $conn->exec("drop table IF EXISTS image_entry");
        $conn->exec("drop table IF EXISTS competition");
    }
}
