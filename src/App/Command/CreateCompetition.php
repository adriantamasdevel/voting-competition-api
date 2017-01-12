<?php

namespace App\Command;

use App\Model\Entity\Competition;
use App\Repo\CompetitionRepo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Model\Entity\ImageEntry;

class CreateCompetition extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);

        $this->setDescription("Creates a competition - values are hard coded in the PHP file.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var $competitionRepo CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];

        $closeDatestamp = new \DateTime("2016-06-10 24:00:00");

        $competition = new Competition(
            null,    //$competitionId,
            "Testing creating ",    //$title,
            "Testing the competitions <b>Huzzah.</b>  ",    //$description,
            $closeDatestamp,    //$dateEntriesClose,
            $closeDatestamp,    //$dateVotesClose,
            ImageEntry::STATUS_UNMODERATED,    //$initialStatusOfImages,
            Competition::STATUS_ANNOUNCED    //$status
        );

        $savedCompetition = $competitionRepo->create($competition);
    }
}
