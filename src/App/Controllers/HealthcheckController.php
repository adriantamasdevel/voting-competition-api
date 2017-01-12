<?php

namespace App\Controllers;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Doctrine\DBAL\Connection;

class HealthcheckController
{
    /**
     * @SWG\Get(path="/healthcheck",
     *   tags={"healthcheck"},
     *   summary="Check if the api can talk with DB",
     *   description="Check if the api can talk with DB",
     *   operationId="checkApis",
     *   produces={"application/json"},
     *   parameters={},
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response"
     *   )
     * )
     *
     *
     * Check if the api can connect to FCSIS and monetizer apis
     *
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function checkApi(Application $app, Request $request)
    {

        $response_live = new JsonResponse(array('live'), 200);
        $response_down = new JsonResponse(array('down'), 200);

        /** @var $connection \Doctrine\DBAL\Connection */
        $connection = $app[Connection::class];
        try {
            $connection->ping();
        }
        catch (\Exception $e) {
            return $response_down;
        }

        return $response_live;
    }

}