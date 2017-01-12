<?php

namespace App\Order;

use App\ApiParams;
use App\Exception\InvalidApiValueException;
use App\Model\RandomOrderToken;
use App\Model\RandomOrderTokenFactory;
use App\Model\Filter\ImageEntryFilter;
use App\Order;

class ImageEntryOrder extends GenericOrder
{
    const FIRST_NAME = "firstName";
    const LAST_NAME = "lastName";
    const STATUS = "status";
    const DATE_SUBMITTED = "dateSubmitted";
    const RANDOM = "rand";

    /** @var  RandomOrderToken */
    private $randomOrderToken = null;

    public static function getAllowed()
    {
        return [
            self::FIRST_NAME,
            self::LAST_NAME,
            self::DATE_SUBMITTED,
            self::RANDOM,
            self::STATUS,
        ];
    }

    public function isSortingByRandom()
    {
        return array_key_exists(self::RANDOM, $this->sortOrder);
    }

    public static function fromArray(array $sortValues)
    {
        throw new \Exception("Please use ImageEntryOrder::fromApiParamsEx. PHP doesn't allow changing method signatures on static inheritance.");
    }

    public static function fromApiParamsEx(
        ApiParams $apiParams,
        RandomOrderTokenFactory $randomOrderTokenFactory,
        $numberOfEntries
    ) {
        $instance = new static();
        $instance->sortParams = $apiParams->getSort();
        $instance->sortOrder = [];
        $instance->calculateSortOrder();

        if ($instance->isSortingByRandom()) {
            $randomTokenInputString = $apiParams->getRandomToken();
            $instance->randomOrderToken = $randomOrderTokenFactory->fromTokenString($randomTokenInputString, $numberOfEntries);
        }

        return $instance;
    }


    public function hasRandomToken()
    {
        return (bool)($this->randomOrderToken === null);
    }

    public function getRandomToken()
    {
        return $this->randomOrderToken;
    }

    protected function calculateSortOrder()
    {
        parent::calculateSortOrder();
        if (array_key_exists(self::RANDOM, $this->sortOrder) === true &&
            count($this->sortOrder) > 1) {
            throw new InvalidApiValueException('random sorting cannot be combined with other sorting.');
        }
    }

    function getFirstNameOrder()
    {
        if (array_key_exists(self::FIRST_NAME, $this->sortOrder) == true) {
            return $this->sortOrder[self::FIRST_NAME];
        }

        return null;
    }

    function getLastNameOrder()
    {
        if (array_key_exists(self::LAST_NAME, $this->sortOrder) == true) {
            return $this->sortOrder[self::LAST_NAME];
        }

        return null;
    }

    function getDateSubmittedOrder()
    {
        if (array_key_exists(self::DATE_SUBMITTED, $this->sortOrder) == true) {
            return $this->sortOrder[self::DATE_SUBMITTED];
        }

        return null;
    }

    function getStatusOrder()
    {
        if (array_key_exists(self::STATUS, $this->sortOrder) == true) {
            return $this->sortOrder[self::STATUS];
        }

        return null;
    }
}

