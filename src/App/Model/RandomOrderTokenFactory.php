<?php

namespace App\Model;

use App\Repo\ImageEntryRepo;
use App\Model\Filter\ImageEntryFilter;

class RandomOrderTokenFactory
{
    /** @var ImageEntryRepo  */
    private $imageEntryRepo;

    const SECONDS_BETWEEN_RAND_CHANGE = 300; // 5 minutes
    const RAND_SLOTS = 64;

    public function __construct(ImageEntryRepo $imageEntryRepo)
    {
        $this->imageEntryRepo = $imageEntryRepo;
    }

    public function createNew($numberOfEntries)
    {
        $seconds = time();
        $remainderSeconds = $seconds % self::SECONDS_BETWEEN_RAND_CHANGE;
        $roundedSeconds = $seconds - $remainderSeconds;

        return new RandomOrderToken(
            $roundedSeconds,
            $numberOfEntries,
            rand(0, self::RAND_SLOTS)
        );
    }

    public function fromTokenString($string, $numberOfEntries)
    {
        $data = @json_decode(stripslashes($string), true);
        $jsonLastError = json_last_error();

        if ($jsonLastError !== JSON_ERROR_NONE || $data === null) {
            return $this->createNew($numberOfEntries);
        }

        if (array_key_exists('time', $data) === false ||
            array_key_exists('numberEntries', $data) === false ||
            array_key_exists('seed', $data) === false) {
            return self::createNew($numberOfEntries);
        }

        $time = intval($data['time']);
        if (abs(time() - $time) > 3600) {
            return self::createNew($numberOfEntries);
        }

        $numberEntries = $data['numberEntries'];
        //@TODO - how to sanity check $numberEntries
        $seed = intval($data['seed']);
        if ($seed < 0 || $seed >= self::RAND_SLOTS) {
            return self::createNew($numberOfEntries);
        }

        //@TODO - check time hasn't expired.
        return new RandomOrderToken(
            $time,
            $numberEntries,
            $seed
        );
    }

}
