<?php

namespace App\Command;
use App\Model\Entity\Competition;
use App\Order\CompetitionOrder;
use App\Model\Filter\CompetitionFilter;
use App\Repo\CompetitionRepo;
use Silex\Application;
use App\Model\Patch\CompetitionPatch;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class CronMonitorCompetitionStatus extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription("Cron task to set competition status based on the dates");
    }



    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var $imageEntryRepo \App\Repo\ImageEntryRepo */
        $competitionRepo = $app[CompetitionRepo::class];

        $competitionOrder = CompetitionOrder::fromArray([]);
        $statuses = implode(',', [Competition::STATUS_OPEN, Competition::STATUS_VOTING]);

        $competitionFilter = CompetitionFilter::fromArray(array('status' => $statuses));
        $offset = 0;
        $limit =  1000;



        try {
            $competitions = $competitionRepo->getCompetitions($offset, $limit, $competitionOrder, $competitionFilter);

            if(count($competitions) == 0) {
                echo ('No competitions found');
                exit(0);
            }

            $now = new \DateTime();

            foreach($competitions as $competition) {
                $status = '';

                $competitionId = $competition->getCompetitionId();
                $dateEntriesClose = $competition->getDateEntriesClose();
                $dateVotesClose = $competition->getDateVotesClose();

                if ($dateEntriesClose <= $now) {
                    $status = 'STATUS_VOTING';
                }

                if ($dateVotesClose <= $now) {
                    $status = 'STATUS_CLOSED';
                }

                if($status != '') {
                    $competitionPatch = CompetitionPatch::fromArray([
                        'status' => $status
                    ]);

                    try {
                        $competitionRepo->update($competitionId, $competitionPatch);

                        printf('Status changed into %s for competition %d ', $status, $competitionId);

                    } catch (\Exception $e) {
                        print $e->getMessage();
                    }

                }

            }
        }
        catch (\Exception $e) {
            print $e->getMessage();
        }

        exit(0);
    }
}
