<?php

namespace App\Model\Patch;

use App\Exception\InvalidApiValueException;
use App\Model\Entity\Competition;
use App\ApiParams;
use App\Model\Entity\ImageEntry;

class CompetitionPatch
{
    /** @var  string */
    protected $description;

    /** @var  string */
    protected $title;

    /** @var  \DateTime */
    protected $dateEntriesClose;

    /** @var  \DateTime */
    protected $dateVotesClose;

    /** @var  string */
    protected $initialStatusOfImages;

    /** @var  string */
    protected $status;

    public static function fromArray(array $params)
    {
        $instance = new self();

        $setters = [
            'status'                => [$instance, 'setStatus'],
            'dateEntriesClose'      => [$instance, 'setDateEntriesClose'],
            'dateVotesClose'        => [$instance, 'setDateVotesClose'],
            'initialStatusOfImages' => [$instance, 'setInitialStatusOfImages'],
            'title'                 => [$instance, 'setTitle'],
            'description'           => [$instance, 'setDescription'],
        ];

        foreach ($params as $key => $value) {
            if (array_key_exists($key, $setters) == false) {
                throw new InvalidApiValueException("Competition patch doesn't know the field '$key' ");
            }
            $setter = $setters[$key];
            if ($setter == null) {
                throw new InvalidApiValueException("Apologies, patch for '$key' is not implemented yet.");
            }

            $setter($value);
        };

        return $instance;
    }


    /**
     * @param ApiParams $apiParams
     * @return CompetitionPatch
     */
    public static function fromApiParams(ApiParams $apiParams)
    {
        $instance = new self();

        if ($apiParams->hasCompetitionDescription()) {
            $instance->description = $apiParams->getCompetitionDescription();
        }

        if ($apiParams->hasCompetitionTitle()) {
            $instance->title = $apiParams->getCompetitionTitle();
        }

        if ($apiParams->hasDateEntriesClose()) {
            $instance->dateEntriesClose = $apiParams->getDateEntriesClose();
        }

        if ($apiParams->hasDateVotesClose()) {
            $instance->dateVotesClose = $apiParams->getDateVotesClose();
        }

        if ($apiParams->hasInitialStatusOfImages()) {
            $instance->initialStatusOfImages = $apiParams->getInitialStatusOfImages();
        }

        if ($apiParams->hasCompetitionStatus()) {
            $instance->setStatus($apiParams->getCompetitionStatus());
        }

        return $instance;
    }


    public function containsUpdate()
    {
        if ($this->description === null &&
            $this->title === null &&
            $this->dateEntriesClose === null &&
            $this->dateVotesClose === null &&
            $this->initialStatusOfImages === null &&
            $this->status === null) {
            return false;
        }
        return true;
    }


    public function setDateEntriesClose(\DateTime $dateEntriesClose)
    {
        $this->dateEntriesClose = $dateEntriesClose;
    }

    public function setDateVotesClose(\DateTime $dateVotesClose)
    {
        $this->dateVotesClose = $dateVotesClose;
    }

    public function setInitialStatusOfImages($initialStatusOfImages)
    {
        ImageEntry::assertIsKnownStatus($initialStatusOfImages);
        $this->initialStatusOfImages = $initialStatusOfImages;
    }

    public function setTitle($title)
    {
        //2TODO check - Competition::TITLE_MAX_LENGTH
        $this->title = $title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setStatus($status)
    {
        Competition::assertIsKnownCompetitionStatus($status);
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getDateEntriesClose()
    {
        return $this->dateEntriesClose;
    }

    /**
     * @return mixed
     */
    public function getDateVotesClose()
    {
        return $this->dateVotesClose;
    }

    /**
     * @return mixed
     */
    public function getInitialStatusOfImages()
    {
        return $this->initialStatusOfImages;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

}
