<?php

namespace App\Repo\Mock;

use App\Model\Entity\ImageEntry;
use App\Order\ImageEntryOrder;
use App\Exception\AlreadyVotedException;
use App\Repo\VoteRepo;

class VoteMockRepo implements VoteRepo
{
    /**
     * @param $imageId
     * @param $ipAddress string
     * @return mixed
     * @throws AlreadyVotedException
     */
    public function addVote($imageId, $ipAddress)
    {
        if (strcmp($ipAddress, '1.2.3.4') === 0) {
            throw new AlreadyVotedException("User with IP address of $ipAddress has already voted on this image");
        }
    }
}
