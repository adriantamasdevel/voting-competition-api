<?php

namespace App\Command;


use Knp\Command\Command;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Model\Entity\Competition;


class MigrateTables extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription("Migrate (or create) all DB tables to the current DB schema.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var $conn Connection */
        $conn = $app[Connection::class];

        $schemaManager = $conn->getSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = new Schema();

        // *****************************************************************
        // *****************************************************************
        $competitionTable = $toSchema->createTable("competition");
        $competitionTable->addColumn("competition_id", Type::INTEGER, array("unsigned" => true, 'autoincrement' => true));
        $competitionTable->addColumn("title", Type::STRING, array("length" => Competition::TITLE_MAX_LENGTH));
        $competitionTable->addColumn("description", Type::TEXT, array("length" => Competition::DESCRIPTION_MAX_LENGTH));
        $competitionTable->addColumn("date_entries_close", Type::DATETIME);
        $competitionTable->addColumn("date_votes_close", Type::DATETIME);
        $competitionTable->addColumn("initial_status_of_images", Type::STRING);
        $competitionTable->addColumn("status", Type::STRING);
        $competitionTable->setPrimaryKey(array("competition_id"));

        // *****************************************************************
        // *****************************************************************
        $imageEntryTable = $toSchema->createTable("image_entry");
        $imageEntryTable->addColumn("image_id", Type::STRING, ["length" => 64, 'comment' => 'A UUIDv4 generated outside of the DB.']);
        $imageEntryTable->addColumn("competition_id", Type::INTEGER, ["unsigned" => true, 'comment' => 'Foreign key to competition.']);
        $imageEntryTable->addColumn("first_name", Type::STRING, ["length" => 1024]);
        $imageEntryTable->addColumn("last_name", Type::STRING, ["length" => 1024]);
        $imageEntryTable->addColumn("email", Type::STRING, ["length" => 1024]);
        $imageEntryTable->addColumn("description", Type::TEXT, ["length" => 65535]);
        $imageEntryTable->addColumn("status", Type::STRING);
        $imageEntryTable->addColumn("date_submitted", Type::DATETIME);
        $imageEntryTable->addColumn("ip_address", Type::STRING, ["length" => 64]);
        $imageEntryTable->addColumn("image_extension", Type::STRING, ["length" => 10]);
        $imageEntryTable->addColumn("third_party_opt_in", Type::BOOLEAN, []);
        $imageEntryTable->addUniqueIndex(array("image_id"), 'UNIQUE_IMAGE_ID');
        $imageEntryTable->addForeignKeyConstraint($competitionTable, ["competition_id"], ["competition_id"]);

        // *****************************************************************
        // *****************************************************************
        $voteTable = $toSchema->createTable("vote");
        $voteTable->addColumn("vote_id", Type::INTEGER, array("unsigned" => true, 'autoincrement' => true));
        $voteTable->addColumn("image_id", Type::STRING, ["length" => 64, 'comment' => 'The image entry id']);
        $voteTable->addColumn("ip_address", Type::STRING, ["length" => 64]);
        $voteTable->addUniqueIndex(array("image_id", "ip_address"), 'UNIQUE_IMAGE_ID_IP_ADDRESS');
        $voteTable->addForeignKeyConstraint($imageEntryTable, ["image_id"], ["image_id"]);
        $voteTable->setPrimaryKey(array("vote_id"));

        $sqlArray = $fromSchema->getMigrateToSql($toSchema, $conn->getDatabasePlatform());

        foreach ($sqlArray as $sql) {
            echo "Exec: $sql \n";
            $conn->exec($sql);
        }

        echo "Ok\n";
    }
}
