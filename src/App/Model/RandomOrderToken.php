<?php

namespace App\Model;


class RandomOrderToken
{
    /**
     * The epoch seconds at which this token was generated
     * @var integer
     */
    private $time;

    /**
     * The number of entries that were present when this token was generated.
     * @var integer
     */
    private $numberEntries;

    /**
     * An actually random number to randomize the data.
     * @var integer
     */
    private $seed;

    public function __construct($time, $numberEntries, $seed)
    {
        $this->time = $time;
        $this->numberEntries = $numberEntries;
        $this->seed = $seed;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return json_encode([
            'time' => $this->time,
            'numberEntries' => $this->numberEntries,
            'seed' => $this->seed
        ]);
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return int
     */
    public function getNumberEntries()
    {
        return $this->numberEntries;
    }

    /**
     * @return int
     */
    public function getSeed()
    {
        return $this->seed;
    }
}
