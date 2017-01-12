<?php

namespace App\Command;

use App\Model\Entity\Competition;
use App\Order\CompetitionOrder;
use App\Repo\CompetitionRepo;
use Knp\Command\Command;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Model\Entity\ImageEntry;
use App\Model\Patch\CompetitionPatch;
use App\VariableMap\ArrayVariableMap;
use App\RouteParams;
use App\ApiParams;
use App\Model\Filter\CompetitionFilter;

class UpdateCompetitionStatus extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription("Update the status of all competitions. This is mostly to be used just for making the data be valid after the statuses were added.");
        $this->addArgument(
            'status',
            InputArgument::REQUIRED,
            'The new status of the competition, one of: ' . implode(', ', Competition::getAllowedStatuses())
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();
        $inputStatus = $input->getArgument('status');

        $apiParams = ApiParams::fromArray([
            'status' => $inputStatus,
        ]);

        /** @var $competitionRepo CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];
        $status = $apiParams->getCompetitionStatus();

        $competitionPatch = CompetitionPatch::fromArray([
            'status' => $status,
            'initialStatusOfImages' => ImageEntry::STATUS_UNMODERATED
        ]);
       
        for ($x=0; $x<500; $x++) {
            $competitionRepo->update($x, $competitionPatch);
        }
    }
}
