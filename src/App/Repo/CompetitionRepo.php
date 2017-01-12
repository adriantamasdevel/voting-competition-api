<?php

namespace App\Repo;

use App\Model\Entity\Competition;
use App\Model\CompetitionStats;
use App\Model\Filter\CompetitionFilter;
use App\Order\CompetitionOrder;
use App\Model\Patch\CompetitionPatch;

interface CompetitionRepo
{
    /**
     * @param int $id The ID of the team.
     * @return Competition
     */
    public function getCompetition($id);

    /**
     * @param $offset
     * @param $limit
     * @param CompetitionOrder $competitionOrder
     * @param CompetitionFilter $competitionFilter
     * @return Competition[]
     */
    public function getCompetitions($offset, $limit, CompetitionOrder $competitionOrder, CompetitionFilter $competitionFilter);

    /**
     * @param int $imageId The id of the image entered in a competition.
     * @return Competition
     */
    public function getCompetitionByImageId($imageId);

    /**
     * @param CompetitionFilter $competitionFilter
     * @return int
     */
    public function getCompetitionTotal(CompetitionFilter $competitionFilter);

    /** @return Competition */
    public function create(Competition $competition);

    /**
     * @param $competitionId
     * @param CompetitionPatch $competitionPatch
     * @return mixed
     */
    public function update($competitionId, CompetitionPatch $competitionPatch);

    /**
     * @param $competitionId
     * @return CompetitionStats
     */
    public function getCompetitionStats($competitionId);
}
