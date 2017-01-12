<?php

namespace App\Pagination;


class StandardPagination
{
    public $offset;
    public $returned;
    public $limit;
    public $total;

    public function __construct($returned, $total, $offset, $limit)
    {
        $this->returned = $returned;
        $this->total = $total;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function toArray()
    {
        $data = [];
        $data['offset'] = $this->offset;
        $data['returned'] = $this->returned;
        $data['limit'] = $this->limit;
        $data['total'] = $this->total;

        return $data;
    }
}
