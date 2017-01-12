<?php

namespace App\Command;


use Knp\Command\Command;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RandomiseVoteIpAddress extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription("Updates any entry in the vote table to have a random IP address where the IP address is currently 192.168.72.1.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var $conn Connection */
        $conn = $app[Connection::class];

        $statement = $conn->query('select vote_id, image_id, ip_address from vote where ip_address = "192.168.72.1"');
        $statement->execute();
        $rows = $statement->fetchAll();

        $count = 0;

        foreach ($rows as $row) {
        //try {
            $newIpAddress =
                rand(4, 255).".".
                rand(4, 255).".".
                rand(4, 255).".".
                rand(4, 255);
            $statement = $conn->exec("update vote set ip_address = '$newIpAddress' where vote_id = ".$row['vote_id']."; ");
//        }
//        catch(\Exception $e) {
//        }
            $count++;
        }

        if ($count !== 0) {
            echo "Should have randomised $count votes.\n";
        }
        else {
            echo "No votes to randomise.\n";
        }
    }
}


