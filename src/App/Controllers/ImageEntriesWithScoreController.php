<?php

namespace App\Controllers;

use App\ApiParamsFactory;
use App\Auth\Auth;
use App\Model\Filter\ImageEntryWithScoreFilter;
use App\Model\ResponseFactory;
use App\Order\ImageEntryWithScoreOrder;
use App\Pagination\StandardPagination;
use App\Repo\ImageEntryWithScoreRepo;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use App\Order\ImageEntryOrderFactory;
use App\Order\ImageEntryWithScoreOrderFactory;

class ImageEntriesWithScoreController
{
    /**
     * @SWG\Get(path="/imageEntriesWithScore",
     *   tags={"imageEntries"},
     *   description="Gets the image entries",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="competitionIdFilter",
     *     in="query",
     *     description="Filter the image entries by a competitionId, or comma separated competitionIds",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="statusFilter",
     *     in="query",
     *     description="Filter the image entries by a particular status. Valid values are 'STATUS_UNMODERATED', 'STATUS_VERIFIED', 'STATUS_HIDDEN', 'STATUS_BLOCKED' or comma separated combinations.",
     *     type="string",
     *     required=false
     *   ),
     *     @SWG\Parameter(
     *     name="imageWidth",
     *     in="query",
     *     description="Return images with defined width",
     *     type="integer",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="randomToken",
     *     in="query",
     *     description="When the sort is set to random, the API will return a random token. Passing this token back in will use the same 'view' into the random data.",
     *     type="string",
     *     required=false
     *   ),
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
     *     description="How to sort the results, which can be csv values of 'dateSubmitted', 'firstName', 'lastName', 'rand', 'status', and 'score'. You can set minus (-) to sort descdending, e.g. 'status,-score' to sort by status ascending and then score descending.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/imageEntryWithScore")
     *   ),
     *   @SWG\Response(response=400,  description="Parameters not acceptable not found")
     * )
     */
    public function getImageEntriesWithScore(
        Application $app,
        Request $request
    ) {
        /** @var $imageEntryWithScoreRepo \App\Repo\ImageEntryWithScoreRepo */
        $imageEntryWithScoreRepo = $app[ImageEntryWithScoreRepo::class];

        /** @var $auth \App\Auth\Auth */
        $auth = $app[Auth::class];

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);
        $apiParams = $apiParamsFactory->createFromRouteParams([]);

        /** @var $imageEntryOrderFactory \App\Order\ImageEntryWithScoreOrderFactory */
        $imageEntryOrderFactory =  $app[ImageEntryWithScoreOrderFactory::class];

        $imageEntryFilter = ImageEntryWithScoreFilter::fromApiParams($apiParams, $auth);
        $numberOfEntries = $imageEntryWithScoreRepo->getTotalImageEntries($imageEntryFilter);

        /** @var $competitionOrder \App\Order\ImageEntryOrder */
        $imageEntryOrder = $imageEntryOrderFactory->fromApiParams($apiParams, $numberOfEntries);

        $offset = $apiParams->getOffset();
        $limit =  $apiParams->getLimit();

        $imageWidth =  $apiParams->getImageWidth();

        $imageEntriesWithScore = $imageEntryWithScoreRepo->getImageEntriesWithScore($offset, $limit, $imageEntryOrder, $imageEntryFilter);

        $totalImageEntries = $imageEntryWithScoreRepo->getTotalImageEntries($imageEntryFilter);
        $pagination = new StandardPagination(count($imageEntriesWithScore), $totalImageEntries, $offset, $limit);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        $data = ['imageEntriesWithScore' => $imageEntriesWithScore];
        if ($imageEntryOrder->isSortingByRandom()) {
            $data['randomToken'] = $imageEntryOrder->getRandomToken()->serialize();
        }

        return $responseFactory->create(
            $data,
            $pagination,
            $imageWidth
        );
    }
}
