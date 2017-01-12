<?php

namespace App\Order;

use App\ApiParams;
use App\Model\Filter\ImageEntryWithScoreFilter;
use App\Model\RandomOrderTokenFactory;

class ImageEntryWithScoreOrderFactory
{
    private $randomOrderTokenFactory;

    public function __construct(RandomOrderTokenFactory $randomOrderTokenFactory)
    {
        $this->randomOrderTokenFactory = $randomOrderTokenFactory;
    }

    /**
     * @param ApiParams $apiParams
     * @param int $numberOfEntries
     * @return \App\Order\ImageEntryWithScoreOrder
     */
    public function fromApiParams(ApiParams $apiParams, $numberOfEntries)
    {
        return  $imageEntryOrder = ImageEntryWithScoreOrder::fromApiParamsEx(
            $apiParams,
            $this->randomOrderTokenFactory,
            $numberOfEntries
        );
    }
}
