<?php

namespace App\Model;


class ImageEntryCountInfo
{
    public $imageEntryCount;
    public $imageEntryUnmoderatedCount;
    public $imageEntryVerifiedCount;
    public $imageEntryHiddenCount;
    public $imageEntryBlockedCount;

    /**
     * ImageEntryCountInfo constructor.
     * @param $imageEntryCount
     * @param $imageEntryUnmoderatedCount
     * @param $imageEntryVerifiedCount
     * @param $imageEntryHiddenCount
     * @param $imageEntryBlockedCount
     */
    public function __construct(
        $imageEntryCount,
        $imageEntryUnmoderatedCount,
        $imageEntryVerifiedCount,
        $imageEntryHiddenCount,
        $imageEntryBlockedCount
    ) {
        $this->imageEntryCount = $imageEntryCount;
        $this->imageEntryUnmoderatedCount = $imageEntryUnmoderatedCount;
        $this->imageEntryVerifiedCount = $imageEntryVerifiedCount;
        $this->imageEntryHiddenCount = $imageEntryHiddenCount;
        $this->imageEntryBlockedCount = $imageEntryBlockedCount;
    }
}
