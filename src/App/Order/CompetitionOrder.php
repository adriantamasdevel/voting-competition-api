<?php

namespace App\Order;

use App\Order;

class CompetitionOrder extends GenericOrder
{
    const ID = "id";
    const DATE_ENTRIES_CLOSE = "dateEntriesClose";
    const DATE_VOTES_CLOSE = "dateVotesClose";

    public static function getAllowed()
    {
        return [
            self::ID,
            self::DATE_ENTRIES_CLOSE,
            self::DATE_VOTES_CLOSE,
        ];
    }
}
