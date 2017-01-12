<?php

namespace App;

use App\VariableMap\RequestVariableMap;
use Symfony\Component\HttpFoundation\Request;

class ApiParamsFactory
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param array $routeParams
     * @return \App\ApiParams
     */
    public function createFromRouteParams(array $routeParams)
    {
        return new ApiParams(
            new RequestVariableMap($this->request),
            new RouteParams($routeParams)
        );
    }
}
