<?php

namespace App\Repo;

use App\Model\Entity\ImageEntryWithScore;
use App\Order\ImageEntryWithScoreOrder;
use App\Model\Filter\ImageEntryWithScoreFilter;

interface ImageEntryWithScoreRepo
{
    /**
     * @param int $imageEntryId The ID of the team.
     * @return ImageEntryWithScore
     */
    public function getImageEntryWithScore($imageEntryId);

    /**
     * @param $offset
     * @param $limit
     * @param ImageEntryWithScoreOrder $imageEntryWithScoreWithScoreOrder
     * @param ImageEntryWithScoreFilter $imageEntryWithScoreFilter
     * @return ImageEntryWithScore[]
     */
    public function getImageEntriesWithScore(
        $offset,
        $limit,
        ImageEntryWithScoreOrder $imageEntryWithScoreWithScoreOrder,
        ImageEntryWithScoreFilter $imageEntryWithScoreFilter
    );

    public function getTotalImageEntries(ImageEntryWithScoreFilter $imageEntryWithScoreFilter);
}
