<?php

namespace App\Repo;

use App\Exception\AlreadyVotedException;
use App\Exception\UnknownImageException;

interface VoteRepo
{
    /**
     * @param $imageId
     * @param $ipAddress
     * @return mixed
     * @throws AlreadyVotedException
     * @throws UnknownImageException
     */
    public function addVote($imageId, $ipAddress);
}
