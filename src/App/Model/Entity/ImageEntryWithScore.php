<?php

namespace App\Model\Entity;

/**
 * @SWG\Definition(
 *     definition="imageEntryWithScore",
 * )
 */
class ImageEntryWithScore
{
    /**
     * Image score (votes)
     *
     * @var int $score
     * @SWG\Property(type="integer")
     */
    protected $score;

    /**
     * @var ImageEntry $imageEntry
     * @SWG\Property(ref="#/definitions/imageEntry")
     */
    protected $imageEntry;

    public function __construct($score, ImageEntry $imageEntry)
    {
        $this->score = $score;
        $this->imageEntry = $imageEntry;
    }

    public function toArray($includeRestrictedData, $hostAndPath, $imageWidth)
    {
        $data = [];
        $data['score'] = $this->score;
        $data['imageEntry'] = $this->imageEntry->toArray($includeRestrictedData, $hostAndPath, $imageWidth);

        return $data;
    }

    public static function fromArray($data)
    {
        return new ImageEntryWithScore(
            $data['score'],
            ImageEntry::fromArray($data['imageEntry'])
        );
    }

    public static function fromDbData($data)
    {
        $imageEntry = ImageEntry::fromDbData($data);
        $instance = new self($data['score'], $imageEntry);

        return $instance;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return ImageEntry
     */
    public function getImageEntry()
    {
        return $this->imageEntry;
    }
}
