<?php

namespace AppTest\Model\Entity;

use App\Model\Entity\Competition;
use App\Model\Entity\ImageEntry;
use App\Exception\VotingClosedException;

class CompetitionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Competition
     */
    private static function createCompetition()
    {
        $data = [];
        $data['competition_id'] = 1;
        $data['title'] = "test title";
        $data['description'] = "test description";
        $data['date_entries_close'] = '2016-06-02 20:00:00';
        $data['date_votes_close'] = '2016-06-02 20:00:00';
        $data['initial_status_of_images'] = ImageEntry::STATUS_UNMODERATED;
        $data['status'] = Competition::STATUS_OPEN;

        return Competition::fromDbData($data);
    }

    public function testAllowedToVote()
    {
        $competition = $this->createCompetition();
        $currentTime = new \DateTime('2016-06-02 19:00:00');
        assertVotingStillOpen($competition, $currentTime);
        $this->assertTrue(true); // 'getting here means the code is working'
    }

    public function testVotingIsClosed()
    {
        $competition = $this->createCompetition();
        $currentTime = new \DateTime('2016-06-02 21:00:00');
        $this->setExpectedException(VotingClosedException::class);
        assertVotingStillOpen($competition, $currentTime);
    }

    public function testVotingDeadline()
    {
        $competition = $this->createCompetition();
        $currentTime = new \DateTime('2016-06-02 20:00:00');
        assertVotingStillOpen($competition, $currentTime);
    }
}
