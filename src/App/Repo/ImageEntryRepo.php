<?php

namespace App\Repo;

use App\Model\Entity\ImageEntry;
use App\Model\Entity\ImageEntryWithScore;
use App\Model\Entity\ImageEntryPatch;
use App\Order\ImageEntryOrder;
use App\Model\Filter\ImageEntryFilter;
use App\Model\RandomOrderToken;

interface ImageEntryRepo
{
    /**
     * @param int $id The ID of the team.
     * @return ImageEntry
     */
    public function getImageEntry($id);

    /**
     * @param $offset
     * @param $limit
     * @param $imageInfoOrder ImageEntryOrder
     * @return ImageEntry[]
     */
    public function getImageEntries(
        $offset,
        $limit,
        ImageEntryOrder $imageInfoOrder,
        ImageEntryFilter $imageEntryFilter
    );

    public function getTotalImageEntries(ImageEntryFilter $imageEntryFilter);

    public function create(ImageEntry $imageEntry);

    public function update($imageId, ImageEntryPatch $imageEntryPatch);
}
