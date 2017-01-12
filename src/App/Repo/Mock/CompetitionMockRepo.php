<?php

namespace App\Repo\Mock;

use App\Model\Entity\ImageEntry;
use App\Model\Entity\Competition;
use App\Order\CompetitionOrder;
use App\Model\Filter\CompetitionFilter;
use App\Model\Patch\CompetitionPatch;
use App\Repo\CompetitionRepo;
use App\Repo\Mock\ImageEntryMockRepo;

class CompetitionMockRepo implements CompetitionRepo
{
    /**
     * @param int $id The ID of the team.
     * @return Competition
     */
    public function getCompetition($id)
    {
        return Competition::fromDbData(self::getMockData());
    }

    public function getCompetitions($offset, $limit, CompetitionOrder $competitionOrder, CompetitionFilter $competitionFilter)
    {
        $competitionArray = [];
        $competitionArray[] = Competition::fromDbData(self::getMockData());
        $competitionArray[] = Competition::fromDbData(self::getMockData());

        return $competitionArray;
    }

    /**
     * @return int
     */
    public function getCompetitionTotal(CompetitionFilter $competitionFilter)
    {
        return 20;
    }

    public function create(Competition $competition)
    {
        return $competition;
    }

    public function update($competitionId, CompetitionPatch $competitionPatch)
    {
        return $this->getCompetition($competitionId);
    }

    private function getMockData()
    {
        $data = [];

        $data['competition_id'] = "2016-05-24_123";
        $data['title'] = "First competition";
        $data['description'] = "<h1>Hurrah, a competition</h1>";
        $data['date_entries_close'] = "2016-06-01T14:00:28+00:00";
        $data['date_votes_close'] = "2016-06-08T14:00:28+00:00";
        $data['initial_status_of_images'] = 0;
        $data['status'] = 1;

        return $data;
    }
}
