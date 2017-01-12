<?php

namespace App\Controllers;

use App\Auth\Auth;
use App\Repo\VoteRepo;
use App\ApiParamsFactory;
use App\Exception\AlreadyVotedException;
use App\Exception\VotingClosedException;
use App\Repo\ImageEntryWithScoreRepo;
use App\Model\ResponseFactory;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Repo\CompetitionRepo;
use App\ApiParams;

class VotesController
{
    /**
     * Allow a user to vote.
     *
     * @SWG\Post(path="/votes",
     *   tags={"vote"},
     *   summary="Allow users to vote",
     *   operationId="checkApis",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="imageId",
     *     in="query",
     *     description="Image Id",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/imageEntryWithScore")
     *   ),
     *   @SWG\Response(response=409, description="JSON Response")
     * )
     *
     *
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     */
    public function postVote(Application $app, Request $request)
    {
        /** @var $voteRepo \App\Repo\VoteRepo */
        $voteRepo = $app[VoteRepo::class];

        /** @var $auth \App\Auth\Auth */
        $auth = $app[Auth::class];

        /** @var $imageEntryWithScoreRepo \App\Repo\ImageEntryWithScoreRepo */
        $imageEntryWithScoreRepo = $app[ImageEntryWithScoreRepo::class];

        /** @var $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];


        $ipAddress = $request->getClientIp();
        if ($auth->isAllowed(Auth::IMAGE_ENTRY_UPDATE) == true) {
            //For authed users - use a random, non-routeable ip address.
            $ipAddress = createRandomIpAddress();
        }

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);
        $apiParams = $apiParamsFactory->createFromRouteParams([]);
        $imageId = $apiParams->getImageId();

        $competition = $competitionRepo->getCompetitionByImageId($imageId);

        try {
            assertVotingStillOpen($competition);
        }
        catch (VotingClosedException $ave) {
            return createJsonErrorResponse(
                403,
                $ave->getMessage(),
                'Voting for this competition is not open.',
                ApiParams::ERROR_VOTING_IS_NOT_OPEN_FOR_COMPETITION
            );
        }

        try {
            $voteRepo->addVote($imageId, $ipAddress);
        }
        catch (AlreadyVotedException $ave) {
            return createJsonErrorResponse(
                403,
                $ave->getMessage(),
                'IP address has already voted for this image.',
                ApiParams::ERROR_VOTE_FROM_IP_ADDRESS_EXISTS
            );
        }

        $imageEntryWithScore = $imageEntryWithScoreRepo->getImageEntryWithScore($imageId);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['imageEntryWithScore' => $imageEntryWithScore]);
    }
}
