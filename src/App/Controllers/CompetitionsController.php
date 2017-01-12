<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\ApiParamsFactory;
use App\ErrorStatus\BadRequestError;
use App\Order\CompetitionOrder;
use App\Model\Entity\Competition;
use App\Model\Filter\CompetitionFilter;
use App\Model\ResponseFactory;
use App\Model\Patch\CompetitionPatch;
use App\Pagination\StandardPagination;
use App\Repo\CompetitionRepo;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class CompetitionsController
{
    /**
     * @SWG\Get(path="/competitions",
     *   tags={"competitions"},
     *   summary="Return a list of competitions.",
     *   description="Gets the competitions",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="offset",
     *     in="query",
     *     description="Pagination offset, default of 0.",
     *     type="integer",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Pagination limit, default of 25.",
     *     type="integer",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="sort",
     *     in="query",
     *     description="How to sort the results, which can be csv values of 'id', 'dateEntriesClose', and 'dateVotesClose'. You can set minus (-) to sort descdending, e.g. '-id' to sort by competition id descending.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/competition")
     *   ),
     *   @SWG\Response(response=400,  description="Parameters not acceptable not found")
     * )
     */
    public function getCompetitions(
        Application $app,
        Request $request
    ) {
        /** @var $auth \App\Auth\Auth */
        $auth = $app[Auth::class];

        /** @var $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);
        $apiParams = $apiParamsFactory->createFromRouteParams([]);

        $competitionOrder = CompetitionOrder::fromApiParams($apiParams);
        $competitionFilter = CompetitionFilter::fromApiParams($apiParams);

        $offset = $apiParams->getOffset();
        $limit =  $apiParams->getLimit();

        $competitions = $competitionRepo->getCompetitions($offset, $limit, $competitionOrder, $competitionFilter);
        $totalCompetitions = $competitionRepo->getCompetitionTotal($competitionFilter);
        $pagination = new StandardPagination(count($competitions), $totalCompetitions, $offset, $limit);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['competitions' => $competitions], $pagination);
    }

    /**
     * @SWG\Get(path="/competitions/{competitionId}",
     *   tags={"competitions"},
     *   summary="Return a single competition and return JSON response",
     *   description="Return a single competition",
     *   produces={"application/json"},
     *   parameters={},
     *   @SWG\Parameter(
     *     name="competitionId",
     *     in="path",
     *     description="Competition ID",
     *     type="string",
     *     required=true
     *   ),
     *    @SWG\Parameter(
     *     name="Accept-language",
     *     in="header",
     *     description="Required language/locale (defaults to gb)",
     *     type="string",
     *     required=true,
     *     default="gb",
     *     @SWG\Schema(ref="")
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/competition")
     *   ),
     *   @SWG\Response(response=404,  description="Competition not found")
     *
     * )
     */
    public function getCompetition(
        Application $app,
        $competitionId,
        Request $request
    ) {
        /** @var $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];
        $competition = $competitionRepo->getCompetition($competitionId);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['competition' => $competition]);
    }

    /**
     * @SWG\Post(path="/competitions",
     *   tags={"competitions"},
     *   description="Create a competition. Only available through the admin interface.",
     *   consumes={
     *      "application/json",
     *      "text/json"
     *   },
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="title",
     *     in="formData",
     *     description="The title of the competition.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="status",
     *     in="formData",
     *     description="The status of the competition. Valid values are 'STATUS_ANNOUNCED', 'STATUS_OPEN', 'STATUS_VOTING', 'STATUS_CLOSED', 'STATUS_HIDDEN'",
     *     type="string",
     *     required=true,
     *     enum={"STATUS_ANNOUNCED", "STATUS_OPEN", "STATUS_VOTING", "STATUS_CLOSED", "STATUS_HIDDEN"}
     *   ),
     *   @SWG\Parameter(
     *     name="initialStatusOfImages",
     *     in="formData",
     *     description="The initial status of the images. Valid values are 'STATUS_UNMODERATED', 'STATUS_VERIFIED', 'STATUS_HIDDEN', 'STATUS_BLOCKED'",
     *     type="string",
     *     required=true,
     *     enum={"STATUS_UNMODERATED", "STATUS_VERIFIED", "STATUS_HIDDEN", "STATUS_BLOCKED"}
     *   ),
     *   @SWG\Parameter(
     *     name="description",
     *     in="formData",
     *     description="The description of the competition.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="dateEntriesClose",
     *     in="formData",
     *     description="The datetime when entry to the competition closes in ISO860 format like 2016-06-29T13:31:33+0000.",
     *     type="string",
     *     format="date-time",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="dateVotesClose",
     *     in="formData",
     *     description="The datetime when voting for the competition closes in ISO860 format like 2016-06-29T13:31:33+0000.",
     *     type="string",
     *     format="date-time",
     *     required=true
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/competition")
     *   ),
     *   @SWG\Response(response=400,  description="Parameters not acceptable not found")
     * )
     */
    public function postCompetition(Application $app, Request $request)
    {
        /** @var $auth \App\Auth\Auth */
        $auth = $app[Auth::class];
        $auth->checkAllowed(Auth::COMPETITION_CREATE);

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);
        $apiParams = $apiParamsFactory->createFromRouteParams([]);

        /** @var $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];
        $competition = new Competition(
            null,    //$competitionId,
            $apiParams->getCompetitionTitle(),
            $apiParams->getCompetitionDescription(),    //$description,
            $apiParams->getDateEntriesClose(),
            $apiParams->getDateVotesClose(),
            $apiParams->getInitialStatusOfImages(),
            $apiParams->getCompetitionStatus()
        );

        $competition = $competitionRepo->create($competition);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['competition' => $competition]);
    }

    /**
     * @SWG\Patch(path="/competitions/{competitionId}",
     *   tags={"competitions"},
     *   description="Update a competition. Only available through the admin interface.",
     *   consumes={
     *      "application/json",
     *      "text/json"
     *   },
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="competitionId",
     *     in="path",
     *     description="The competition to update.",
     *     type="integer",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="title",
     *     in="formData",
     *     description="The title of the competition.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="status",
     *     in="formData",
     *     description="The status of the competition. Valid values are 'STATUS_ANNOUNCED', 'STATUS_OPEN', 'STATUS_VOTING', 'STATUS_CLOSED', 'STATUS_HIDDEN'",
     *     type="string",
     *     enum={"STATUS_ANNOUNCED", "STATUS_OPEN", "STATUS_VOTING", "STATUS_CLOSED", "STATUS_HIDDEN"},
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="initialStatusOfImages",
     *     in="formData",
     *     description="The initial status of the images. Valid values are 'STATUS_UNMODERATED', 'STATUS_VERIFIED', 'STATUS_HIDDEN', 'STATUS_BLOCKED'",
     *     enum={"STATUS_UNMODERATED", "STATUS_VERIFIED", "STATUS_HIDDEN", "STATUS_BLOCKED"},
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="description",
     *     in="formData",
     *     description="The description of the competition.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="dateEntriesClose",
     *     in="formData",
     *     description="The datetime when entry to the competition closes.",
     *     type="string",
     *     format="date-time",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="dateVotesClose",
     *     in="formData",
     *     description="The datetime when voting for the competition closes.",
     *     type="string",
     *     format="date-time",
     *     required=false
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/imageEntry")
     *   ),
     *   @SWG\Response(response=400,  description="Parameters not acceptable not found")
     * )
     */
    public function patchCompetition(Application $app, $competitionId, Request $request)
    {
        /** @var $auth \App\Auth\Auth */
        $auth = $app[Auth::class];
        $auth->checkAllowed(Auth::COMPETITION_UPDATE);

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);

        $apiParams = $apiParamsFactory->createFromRouteParams(['competitionId' => $competitionId]);

        /** @var $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];

        $competitionPatch = CompetitionPatch::fromApiParams($apiParams);

        if ($competitionPatch->containsUpdate() === false) {
            $error = new BadRequestError('No known fields set');
            return new JsonResponse($error->toArray(), $error->getStatusCode());
        }

        $competitionRepo->update($competitionId, $competitionPatch);
        $competition = $competitionRepo->getCompetition($competitionId);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['competition' => $competition]);
    }


    /**
     * @SWG\Get(path="/competitions/{competitionId}/stats",
     *   tags={"competitions"},
     *   summary="Return the interaction stats for single competition as JSON",
     *   description="Return a single competition",
     *   produces={"application/json"},
     *   parameters={},
     *   @SWG\Parameter(
     *     name="competitionId",
     *     in="path",
     *     description="Competition ID",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/competitionStats")
     *   ),
     *   @SWG\Response(response=404,  description="Competition not found")
     * )
     */
    public function getCompetitionStats(
        Application $app,
        $competitionId,
        Request $request
    ) {
        /** @var $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];

        $competitionStats = $competitionRepo->getCompetitionStats($competitionId);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['competitionStats' => $competitionStats]);
    }


}
