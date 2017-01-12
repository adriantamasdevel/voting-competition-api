<?php

namespace App\Controllers;

use App\ApiParams;
use App\ApiParamsFactory;
use App\Auth\Auth;
use App\ErrorStatus\BadRequestError;
use App\Exception\ImageEntryClosedException;
use App\Model\Entity\ImageEntry;
use App\Model\Entity\ImageEntryPatch;
use App\Model\Filter\ImageEntryFilter;
use App\Model\ResponseFactory;
use App\Model\Validator\ImageEntryValidator;
use App\Order\ImageEntryOrderFactory;
use App\Pagination\StandardPagination;
use App\Repo\CompetitionRepo;
use App\Repo\ImageEntryRepo;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class ImageEntriesController
{
    /**
     * @SWG\Get(path="/imageEntries",
     *   tags={"imageEntries"},
     *   description="Gets the image entries",
     *   consumes={
     *      "application/json",
     *      "text/json"
     *   },
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="competitionIdFilter",
     *     in="query",
     *     description="Filter the image entries by competitionId. Use comma separated values for multiple competition",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="statusFilter",
     *     in="query",
     *     description="Filter the image entries by status. Use comma separated values for multiple status. Valid values are 'STATUS_UNMODERATED', 'STATUS_VERIFIED', 'STATUS_HIDDEN', 'STATUS_BLOCKED'",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
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
     *     description="How to sort the results, which can be csv values of 'dateSubmitted', 'firstName', 'lastName', 'rand', and 'status'. You can set minus (-) to sort descdending, e.g. 'status,-dateSubmitted' to sort by status ascending and then dateSubmitted descending.",
     *     type="string",
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
    public function getImageEntries(
        Application $app
    ) {
        /** @var $imageEntryRepo \App\Repo\ImageEntryRepo */
        $imageEntryRepo = $app[ImageEntryRepo::class];

        /** @var $auth \App\Auth\Auth */
        $auth = $app[Auth::class];

        $apiParams = $app[\App\ApiParams::class];
        $imageEntryFilter = ImageEntryFilter::fromApiParams($apiParams, $auth);

        /** @var $imageEntryOrderFactory \App\Order\ImageEntryOrderFactory */
        $imageEntryOrderFactory =  $app[ImageEntryOrderFactory::class];
        $numberOfEntries = $imageEntryRepo->getTotalImageEntries($imageEntryFilter);
        $imageEntryOrder = $imageEntryOrderFactory->fromApiParams($apiParams, $numberOfEntries);

        $offset = $apiParams->getOffset();
        $limit =  $apiParams->getLimit();
        $imageWidth =  $apiParams->getImageWidth();
        $imageEntries = $imageEntryRepo->getImageEntries($offset, $limit, $imageEntryOrder, $imageEntryFilter);

        $totalImageEntries = $imageEntryRepo->getTotalImageEntries($imageEntryFilter);
        $pagination = new StandardPagination(count($imageEntries), $totalImageEntries, $offset, $limit);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        $data = ['imageEntries' => $imageEntries];
        if ($imageEntryOrder->isSortingByRandom()) {
            $data['randomToken'] = $imageEntryOrder->getRandomToken()->serialize();
        }

        return $responseFactory->create(
            $data,
            $pagination,
            $imageWidth
        );
    }

    /**
     * @SWG\Post(path="/imageEntries",
     *   tags={"imageEntries"},
     *   description="Create an image entry for a competition",
     *   consumes={
     *      "application/json",
     *      "text/json"
     *   },
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="firstName",
     *     in="formData",
     *     description="The first name of the user.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="lastName",
     *     in="formData",
     *     description="The last name of the user.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="email",
     *     in="formData",
     *     description="The email of the user.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="description",
     *     in="formData",
     *     description="The description of the image.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="competitionId",
     *     in="formData",
     *     description="The competition this image is an entry for.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="imageId",
     *     in="formData",
     *     description="The imageId that this entry is for.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="thirdPartyOptIn",
     *     in="formData",
     *     description="Whether the user has opted into receiving 3rd party marketing.",
     *     type="boolean",
     *     required=false
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/imageEntry")
     *   ),
     *   @SWG\Response(response=400,  description="Parameters not acceptable not found"),
     *   @SWG\Response(response=409,  description="Image with image Id has alreayd been used for an entry.")
     * )
     */
    public function postImageEntry(
        Application $app,
        Request $request
    ) {
        /** @var $imageEntryRepo \App\Repo\ImageEntryRepo */
        $imageEntryRepo = $app[ImageEntryRepo::class];

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);

        $apiParams = $apiParamsFactory->createFromRouteParams([]);

        $imageId = $apiParams->getImageId();
        $firstName = $apiParams->getFirstName();
        $lastName = $apiParams->getLastName();
        $userEmail  = $apiParams->getUserEmail();
        $description = $apiParams->getImageDescription();
        $thirdPartyOptIn = $apiParams->getThirdPartyOptIn();

        //@TODO read from competition
        $imageExtension = getImageExtension($app, $imageId);
        $competitionId = $apiParams->getCompetitionId();
        $dateSubmitted = new \DateTime();

        //This throws an exception if the image is not found
        $imageFilePath = findFileByImageId($app, $imageId);

        /** @var  $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];
        // This throws an exception if the competition is not found
        $competition = $competitionRepo->getCompetition($competitionId);
        $status = $competition->getInitialStatusOfImages();

        try {
            assertImageEntryStillOpen($competition);
        }
        catch (ImageEntryClosedException $iece) {
            return createJsonErrorResponse(
                403,
                $iece->getMessage(),
                'Images can not be entered at this time.',
                ApiParams::ERROR_IMAGE_ENTRY_NOT_OPEN_FOR_COMPETITION
            );
        }

        $imageEntry = new ImageEntry(
            $imageId,
            $firstName, // validate
            $lastName, // validate
            $userEmail, // validate
            $description, // validate
            $status, // validate
            $dateSubmitted,
            $ipAddress = $request->getClientIp(),
            $imageExtension,
            $competitionId,
            $thirdPartyOptIn
        );

        $imageEntryValidator = new ImageEntryValidator();

        if ($imageEntryValidator->hasErrors($imageEntry) === true) {
            $errors = $imageEntryValidator->getErrors($imageEntry);

            return createJsonErrorResponse(
                403,
                "Form has errors",
                "Form has errors",
                ApiParams::ERROR_FORM_ERRORS,
                ['formErrors' => $errors]
            );
        }


        $imageEntryRepo->create($imageEntry);

        $obj = new \StdClass;
        $obj->status = 'Ok';

        return new JsonResponse(['data' => $obj], 200);
    }


    /**
     * @SWG\Get(path="/imageEntries/{imageId}",
     *   tags={"imageEntries"},
     *   description="Gets a single image entry",
     *   consumes={
     *      "application/json",
     *      "text/json"
     *   },
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="imageId",
     *     in="path",
     *     description="The id of the imageEntry to get.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="JSON Response",
     *     @SWG\Schema(ref="#/definitions/imageEntry")
     *   ),
     *   @SWG\Response(response=400,  description="Parameters not acceptable not found")
     * )
     */
    public function getImageEntry(
        Application $app,
        $imageId,
        Request $request
    ) {
        /** @var $imageEntryRepo \App\Repo\ImageEntryRepo */
        $imageEntryRepo = $app[ImageEntryRepo::class];

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);
        $apiParams = $apiParamsFactory->createFromRouteParams(['imageId' => $imageId]);

        $filteredImageId = $apiParams->getImageId();

        $imageEntry = $imageEntryRepo->getImageEntry($filteredImageId);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['imageEntry' => $imageEntry]);
    }


    /**
     * @SWG\Patch(path="/imageEntries/{imageId}",
     *   tags={"imageEntries"},
     *   description="Update an image entry for a competition. Only available through the admin interface.",
     *   consumes={
     *      "application/json",
     *      "text/json"
     *   },
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="imageId",
     *     in="path",
     *     description="The imageEntry to update.",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="firstName",
     *     in="formData",
     *     description="The first name of the user.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="lastName",
     *     in="formData",
     *     description="The last name of the user.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="email",
     *     in="formData",
     *     description="The email of the user.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="description",
     *     in="formData",
     *     description="The description of the image.",
     *     type="string",
     *     required=false
     *   ),
     *   @SWG\Parameter(
     *     name="status",
     *     in="formData",
     *     description="The status of the image. Valid values are 'STATUS_UNMODERATED', 'STATUS_VERIFIED', 'STATUS_HIDDEN', 'STATUS_BLOCKED'",
     *     type="string",
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
    public function patchImageEntry(Application $app, $imageId, Request $request)
    {
        /** @var $auth \App\Auth\Auth */
        $auth = $app[Auth::class];
        $auth->checkAllowed(Auth::IMAGE_ENTRY_UPDATE);

        /** @var $apiParamsFactory \App\ApiParamsFactory */
        $apiParamsFactory = new ApiParamsFactory($request);

        $apiParams = $apiParamsFactory->createFromRouteParams(['imageId' => $imageId]);

        /** @var $imageEntryRepo \App\Repo\ImageEntryRepo */
        $imageEntryRepo = $app[ImageEntryRepo::class];

        $imageEntryPatch = new ImageEntryPatch();

        if ($apiParams->hasFirstName()) {
            $imageEntryPatch->firstName = $apiParams->getFirstName();
        }

        if ($apiParams->hasLastName()) {
            $imageEntryPatch->lastName = $apiParams->getLastName();
        }

        if ($apiParams->hasUserEmail()) {
            $imageEntryPatch->email = $apiParams->getUserEmail();
        }

        if ($apiParams->hasImageDescription()) {
            $imageEntryPatch->description = $apiParams->getImageDescription();
        }

        if ($apiParams->hasStatus()) {
            $imageEntryPatch->status = $apiParams->getImageStatus();
        }

        if ($imageEntryPatch->containsUpdate() === false) {
            $error = new BadRequestError('No known fields set');
            return new JsonResponse($error->toArray(), $error->getStatusCode());
        }

        $imageEntryRepo->update($imageId, $imageEntryPatch);
        $imageEntry = $imageEntryRepo->getImageEntry($imageId);

        /** @var $responseFactory \App\Model\ResponseFactory */
        $responseFactory = $app[ResponseFactory::class];

        return $responseFactory->create(['imageEntry' => $imageEntry]);
    }
}
