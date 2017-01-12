<?php

namespace App\Order;

use App\ApiParams;
use App\Model\Filter\ImageEntryFilter;
use App\Model\RandomOrderTokenFactory;

class ImageEntryOrderFactory
{
    private $randomOrderTokenFactory;

    public function __construct(RandomOrderTokenFactory $randomOrderTokenFactory)
    {
        $this->randomOrderTokenFactory = $randomOrderTokenFactory;
    }

    /**
     * @param ApiParams $apiParams
     * @param int $numberOfEntries
     * @return \App\Order\ImageEntryOrder
     */
    public function fromApiParams(ApiParams $apiParams, $numberOfEntries)
    {
        return  $imageEntryOrder = ImageEntryOrder::fromApiParamsEx(
            $apiParams,
            $this->randomOrderTokenFactory,
            $numberOfEntries
        );
    }
}
