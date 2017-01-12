<?php

namespace App\Order;

use App\ApiParams;
use App\Order;
use App\Exception\InvalidApiValueException;

abstract class GenericOrder implements OrderInfo
{

    protected $sortParams;

    protected $sortOrder = [];

    protected function __construct()
    {
        //Nothing to do, just prevent accidental construction.
    }

    public static function fromArray(array $sortValues)
    {
        $instance = new static();
        $instance->sortParams = $sortValues;
        $instance->sortOrder = [];
        $instance->calculateSortOrder();

        return $instance;
    }

    public static function fromApiParams(ApiParams $apiParams)
    {
        $instance = new static();
        $instance->sortParams = $apiParams->getSort();
        $instance->sortOrder = [];
        $instance->calculateSortOrder();

        return $instance;
    }

    protected function calculateSortOrder()
    {
        if ($this->sortParams === null) {
            return;
        }

        foreach ($this->sortParams as $sortParam) {
            $normalizedSortParam = $sortParam;
            $order = Order::SORT_ASC;
            if (substr($sortParam, 0, 1) === '-') {
                $order = Order::SORT_DESC;
                $normalizedSortParam = mb_substr($sortParam, 1);
            }

            if (in_array($normalizedSortParam, $this->getAllowed()) === false) {
                throw new InvalidApiValueException("Sort parameters [$sortParam] is not known.");
            }

            $this->sortOrder[$normalizedSortParam] = $order;
        }
    }

    public function getOrder()
    {
        return $this->sortOrder;
    }
}
