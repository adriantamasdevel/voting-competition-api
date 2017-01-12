<?php

namespace App\Model;

/**
 * @SWG\Definition(
 *     definition="competitionStats",
 * )
 */
class CompetitionStats
{
    /**
     * @var integer
     * @SWG\Property(type="integer")
     */
    private $imageEntryCount;

    /**
     * @var integer
     * @SWG\Property(type="integer")
     */
    private $imageEntryUnmoderatedCount;

    /**
     * @var integer
     * @SWG\Property(type="integer")
     */
    private $imageEntryVerifiedCount;

    /**
     * @var integer
     * @SWG\Property(type="integer")
     */
    private $imageEntryHiddenCount;

    /**
     * @var integer
     * @SWG\Property(type="integer")
     */
    private $imageEntryBlockedCount;

    /**
     * @var integer
     * @SWG\Property(type="integer")
     */
    private $votesCount;

    /**
     * @var integer
     * @SWG\Property(type="integer")
     */
    private $uniqueVoterCount;

    /**
     * CompetitionStats constructor.
     * @param $imageEntryCount
     * @param $imageEntryUnmoderatedCount
     * @param $imageEntryVerifiedCount
     * @param $imageEntryHiddenCount
     * @param $imageEntryBlockedCount
     * @param $votesCount
     * @param int $uniqueUserVotingCount
     */
    public function __construct(
        $imageEntryCount,
        $imageEntryUnmoderatedCount,
        $imageEntryVerifiedCount,
        $imageEntryHiddenCount,
        $imageEntryBlockedCount,
        $voteCount,
        $uniqueVoterCount
    ) {
        $this->imageEntryCount = $imageEntryCount;
        $this->imageEntryUnmoderatedCount = $imageEntryUnmoderatedCount;
        $this->imageEntryVerifiedCount = $imageEntryVerifiedCount;
        $this->imageEntryHiddenCount = $imageEntryHiddenCount;
        $this->imageEntryBlockedCount = $imageEntryBlockedCount;
        $this->votesCount = $voteCount;
        $this->uniqueVoterCount = $uniqueVoterCount;
    }

    public function toArray()
    {
        return [
            'imageEntryCount' => $this->imageEntryCount,
            'imageEntryUnmoderatedCount' => $this->imageEntryUnmoderatedCount,
            'imageEntryVerifiedCount' => $this->imageEntryVerifiedCount,
            'imageEntryHiddenCount' => $this->imageEntryHiddenCount,
            'imageEntryBlockedCount' => $this->imageEntryBlockedCount,
            'votesCount' => $this->votesCount,
            'uniqueVoterCount' => $this->uniqueVoterCount,
        ];
    }


    public static function fromArray($data)
    {
        return new self(
            $data['imageEntryCount'],
            $data['imageEntryUnmoderatedCount'],
            $data['imageEntryVerifiedCount'],
            $data['imageEntryHiddenCount'],
            $data['imageEntryBlockedCount'],
            $data['votesCount'],
            $data['uniqueVoterCount']
        );
    }

    /**
     * @return mixed
     */
    public function getImageEntryCount()
    {
        return $this->imageEntryCount;
    }

    /**
     * @return mixed
     */
    public function getImageEntryUnmoderatedCount()
    {
        return $this->imageEntryUnmoderatedCount;
    }

    /**
     * @return mixed
     */
    public function getImageEntryVerifiedCount()
    {
        return $this->imageEntryVerifiedCount;
    }

    /**
     * @return mixed
     */
    public function getImageEntryHiddenCount()
    {
        return $this->imageEntryHiddenCount;
    }

    /**
     * @return mixed
     */
    public function getImageEntryBlockedCount()
    {
        return $this->imageEntryBlockedCount;
    }

    /**
     * @return mixed
     */
    public function getVotesCount()
    {
        return $this->votesCount;
    }

    /**
     * @return int
     */
    public function getUniqueUserVotingCount()
    {
        return $this->uniqueVoterCount;
    }
}
