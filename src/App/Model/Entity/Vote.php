<?php

namespace App\Model\Entity;

/**
 * @SWG\Definition(
 *     definition="vote",
 * )
 */
class Vote
{
    /**
     * Primary key
     *
     * @var string $voteId
     * @SWG\Property(type="int")
     */
    protected $voteId;

    /**
     * Which imageEntry this was a vote for
     *
     * @var string $imageId
     * @SWG\Property(type="string")
     */
    protected $imageId;

    /**
     * Vote $ipAddress - where the vote came from
     *
     * @var int $ipAddress
     * @SWG\Property(type="integer")
     */
    protected $ipAddress;

    public function __construct($voteId, $imageId, $ipAddress)
    {
        $this->voteId = $voteId;
        $this->imageId = $imageId;
        $this->ipAddress = $ipAddress;
    }

    public function toArray()
    {
        $data = [];
        $data['voteId'] = $this->voteId;
        $data['imageId'] = $this->imageId;
        $data['ipAddress'] = $this->ipAddress;

        return $data;
    }

    public function toDbData()
    {
        $data = [];
        $data['vote_id'] = $this->voteId;
        $data['image_id'] = $this->imageId;
        $data['ip_address'] = $this->ipAddress;

        return $data;
    }

    public static function fromDbData($data)
    {
        $instance = new self(
            $data['vote_id'],
            $data['image_id'],
            $data['ip_address']
        );

        return $instance;
    }
}
