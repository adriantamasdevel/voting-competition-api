<?php

namespace App\Model\Filter;

use App\ApiParams;
//use App\InvalidApiValueException;
//use App\Model\Entity\ImageEntry;

class CompetitionFilter
{
    public $statuses = null;

    // prevent accidental construction
    protected function __construct() {}

    public static function fromArray(array $array)
    {
        $instance = new self();

        if(!empty($array)){

            if(array_key_exists('status', $array)) {
                $competitionStatusFilter = $array['status'];
                $instance->statuses = parseCompetitionStatusFilter($competitionStatusFilter);
            }

        }
        return $instance;
    }

    public static function fromApiParams(ApiParams $apiParams)
    {
        $instance = new self();
//        $imageEntryStatusFilter = $apiParams->getImageEntryStatusFilter();
//        $instance->allowedStatuses = parseImageEntryStatusFilter($imageEntryStatusFilter);
//
//        $competitionIdFilter = $apiParams->getCompetitionIdFilter();
//        $instance->allowedCompetitionIds = parseCompetitionIdFilter($competitionIdFilter);

        return $instance;
    }
}
