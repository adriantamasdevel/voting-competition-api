<?php

namespace App\Model\Entity;

use App\Exception\InvalidApiValueException;
use App\Exception\VotingClosedException;

/**
 * @SWG\Definition(
 *     definition="competition",
 * )
 */
class Competition
{
    const STATUS_ANNOUNCED  = 'STATUS_ANNOUNCED'; // before people can submit a photo
    const STATUS_OPEN       = 'STATUS_OPEN'; // people can submit images and vote on them
    const STATUS_VOTING     = 'STATUS_VOTING'; // people can't submit images but they can still vote
    const STATUS_CLOSED     = 'STATUS_CLOSED'; // people can't submit images or vote
    const STATUS_HIDDEN     = 'STATUS_HIDDEN'; // public can't see the competition

    const TITLE_MAX_LENGTH = 1024;

    const DESCRIPTION_MAX_LENGTH = 65535;

    public static function getAllowedStatuses()
    {
        return [
            self::STATUS_ANNOUNCED,
            self::STATUS_OPEN,
            self::STATUS_VOTING,
            self::STATUS_CLOSED,
            self::STATUS_HIDDEN,
        ];
    }

    public static function assertIsKnownCompetitionStatus($status)
    {
        if (in_array($status, self::getAllowedStatuses()) == false) {
            throw new InvalidApiValueException("Unknown status type '$status'");
        }
    }

    /**
     * Primary key
     *
     * @var string $competitionId
     * @SWG\Property(type="integer")
     */
    protected $competitionId;

    /**
     * Competition title
     *
     * @var string $title
     * @SWG\Property(type="string")
     */
    protected $title;

    /**
     * Competition description
     *
     * @var string $description
     * @SWG\Property(type="string")
     */
    protected $description;

    /**
     * @var \DateTime $dateEntriesClose Competition close entries datetime
     *
     * @SWG\Property(type="string")
     */
    protected $dateEntriesClose;

    /**
     * @var \DateTime Competition close votes datetime
     *
     * @var string $dateVotesClose can be datetime
     * @SWG\Property(type="string")
     */
    protected $dateVotesClose;

    /**
     *  initial status of images in Competition
     *
     * @var int $initialStatusOfImages can be STATUS_UNMODERATED, STATUS_VERIFIED, STATUS_HIDDEN, STATUS_BLOCKED
     * @SWG\Property(type="string")
     */
    protected $initialStatusOfImages;

    /**
     * Competition status
     *
     * @var int $status can be STATUS_ANNOUNCED STATUS_OPEN STATUS_VOTING STATUS_CLOSED STATUS_HIDDEN
     * @SWG\Property(type="string")
     */
    protected $status;

    public function __construct(
        $competitionId,
        $title, $description,
        \DateTime $dateEntriesClose,
        \DateTime $dateVotesClose,
        $initialStatusOfImages,
        $status
    ) {
        ImageEntry::assertIsKnownStatus($initialStatusOfImages);

        self::assertIsKnownCompetitionStatus($status);
        $this->competitionId = $competitionId;
        $this->title = $title;
        $this->description = $description;
        $this->dateEntriesClose = $dateEntriesClose;
        $this->dateVotesClose = $dateVotesClose;
        $this->initialStatusOfImages = $initialStatusOfImages;
        $this->status = $status;
    }


    public function toArray()
    {
        $data = [];
        $data['competitionId'] = $this->competitionId;
        $data['title'] = $this->title;
        $data['description'] = $this->description;
        $data['dateEntriesClose'] = $this->dateEntriesClose->format(\DateTime::ISO8601);
        $data['dateVotesClose'] = $this->dateVotesClose->format(\DateTime::ISO8601);
        $data['initialStatusOfImages'] = $this->initialStatusOfImages;
        $data['status'] = $this->status;

        return $data;
    }


    public function toDbData()
    {
        $data = [];
        $data['competition_id'] = $this->competitionId;
        $data['title'] = $this->title;
        $data['description'] = $this->description;
        $data['date_entries_close'] = $this->dateEntriesClose;
        $data['date_votes_close'] = $this->dateVotesClose;
        $data['initial_status_of_images'] = $this->initialStatusOfImages;
        $data['status'] = $this->status;

        return $data;
    }

    public static function fromDbData($data)
    {
        $instance = new self(
            intval($data['competition_id']),
            $data['title'],
            $data['description'],
            new \DateTime($data['date_entries_close']),
            new \DateTime($data['date_votes_close']),
            $data['initial_status_of_images'],
            $data['status']
        );

        return $instance;
    }

    public static function fromArray($data)
    {
        $instance = new self(
            intval($data['competitionId']),
            $data['title'],
            $data['description'],
            new \DateTime($data['dateEntriesClose']),
            new \DateTime($data['dateVotesClose']),
            $data['initialStatusOfImages'],
            $data['status']
        );

        return $instance;
    }

    /**
     * @param string $competitionId
     */
    public function setCompetitionId($competitionId)
    {
        $competitionId = intval($competitionId);
        $this->competitionId = $competitionId;
    }

    /**
     * @return string
     */
    public function getCompetitionId()
    {
        return $this->competitionId;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return \DateTime
     */
    public function getDateEntriesClose()
    {
        return $this->dateEntriesClose;
    }

    /**
     * @return \DateTime
     */
    public function getDateVotesClose()
    {
        return $this->dateVotesClose;
    }

    /**
     * @return int
     */
    public function getInitialStatusOfImages()
    {
        return $this->initialStatusOfImages;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
