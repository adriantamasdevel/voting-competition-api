<?php

namespace App\Model\Filter;

use App\ApiParams;
use App\Exception\InvalidApiValueException;
use App\Model\Entity\ImageEntry;
use App\Auth\Auth;

class ImageEntryWithScoreFilter
{
    public $allowedCompetitionIds = null;
    public $allowedStatuses = null;

    protected function __construct() {}

    public static function fromApiParams(ApiParams $apiParams, Auth $auth)
    {
        $instance = new self();
        $imageEntryStatusFilter = $apiParams->getImageEntryStatusFilter();
        $instance->allowedStatuses = parseImageEntryStatusFilter($imageEntryStatusFilter);

        if ($auth->isAllowed(Auth::IMAGE_ENTRY_VIEW_UNMODERATED) == false) {
            $instance->allowedStatuses = [ImageEntry::STATUS_VERIFIED];
        }

        $competitionIdFilter = $apiParams->getCompetitionIdFilter();
        $instance->allowedCompetitionIds = parseCompetitionIdFilter($competitionIdFilter);

        return $instance;
    }


    public static function createByStatus(array $statuses)
    {
        $instance = new self();
        //@TODO - validate values
        $instance->allowedStatuses = $statuses;
        $instance->allowedCompetitionIds = null;

        return $instance;
    }
}
