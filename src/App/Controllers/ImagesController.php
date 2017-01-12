<?php

namespace App\Controllers;
use App\ErrorStatus\BadRequestError;
use App\ErrorStatus\UnauthorizedError;
use App\ErrorStatus\UnsupportedMediaTypeError;
use App\Repo\CompetitionRepo;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Finder\Finder;
use App\Exception\ImageEntryClosedException;
use App\ApiParams;
use Symfony\Component\HttpFoundation\ParameterBag;

class ImagesController
{
    /**
     * @param Application $app
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     * @SWG\Post(path="/images",
     *   tags={"images"},
     *   summary="Upload Image for competition to server",
     *   description="Returns an image ID",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     description="The image file.",
     *     type="file",
     *     required=true
     *   ),
     *   @SWG\Parameter(
     *     name="competitionId",
     *     in="formData",
     *     description="The competition this image is an entry for.",
     *     type="string",
     *     required=true
     *   ),

     *   @SWG\Response(
     *     response=201,
     *     description="JSON Response"
     *   ),
     *   @SWG\Response(response=400,  description="Malformed request syntax"),
     *   @SWG\Response(response=401,  description="Not a valid captcha code"),
     *   @SWG\Response(response=404,  description="Not Found"),
     *   @SWG\Response(response=415,  description="Not a valid image file")
     *
     * )
     */
    public function postImage(Application $app, Request $request)
    {
        $storage_path = $app['image.upload_path'];
        $file_bag = $request->files;
        $post_bag = $request->request;


        if($post_bag->has('competitionId')) {
            $competitionId = $post_bag->get('competitionId');
        }

        if (empty($competitionId)) {
            $error = new BadRequestError('Malformed request syntax, competitionId not set.');
            return new JsonResponse($error->toArray(), $error->getStatusCode());
        }

        /** @var $competitionRepo \App\Repo\CompetitionRepo */
        $competitionRepo = $app[CompetitionRepo::class];
        $competition = $competitionRepo->getCompetition($competitionId);

        if (empty($competition)) {
            $error = new BadRequestError('Malformed request syntax, unknown competitionId');
            return new JsonResponse($error->toArray(), $error->getStatusCode());
        }

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

        $captchaResponse = $this->getCaptchaResponse($post_bag, $app);
        if ($captchaResponse != null) {
            //Something was wrong with the captcha.
            return $captchaResponse;
        }

        // we have a file param
        if ($file_bag->has('file') == false) {
            $error = new BadRequestError("Request doesn't contain file.");
            return new JsonResponse($error->toArray(), $error->getStatusCode());
        }

        // Do we have an error!
        $image = $file_bag->get('file');
        if ($image->isValid() == false) {
            $error = new BadRequestError("File isn't a valid file.");
            return new JsonResponse($error->toArray(), $error->getStatusCode());
        }

        // Do we have a valid image file
        if($this->isImage($image) == false) {
            // We have an error!
            $error = new UnsupportedMediaTypeError('Not a valid image file');
            return new JsonResponse($error->toArray(), $error->getStatusCode());
        }

        $imageUniqueId = saveImageFile(
            $image->guessExtension(),
            $image->getPathname(),
            $storage_path
        );

        $data = array('image' => array('imageId' => $imageUniqueId));
        $response = new JsonResponse(array('data' => $data), 201); //201 Resource Created

        return $response;
    }


    /**
     * @SWG\Get(path="/images/{imageId}",
     *   tags={"images"},
     *   description="Gets an image with the requested size.",
     *   operationId="addImage",
     *   produces={"application/json"},
     *   parameters={},
     *   @SWG\Parameter(
     *     name="imageId",
     *     in="path",
     *     description="Image Id",
     *     type="string",
     *     required=true
     *   ),
     *   @SWG\Response(
     *     response=200,
     *     description="Binary image data"
     *   ),
     *   @SWG\Response(response=404,  description="Competitions not found")
     *
     * )
     */
    public function getImage(Application $app, $imageId)
    {
        preg_match('/^img_(.+)-(\d+)-(\d+)\.(jpg|gif|png|jpeg)/', $imageId, $output_array);

        if(is_array($output_array) && !empty($output_array) && array_key_exists(1,$output_array)) {
            $imageId = $output_array[1];
        } else {
            $imageId = str_replace('img_', '', $imageId);
        }


        $full_name = findFileByImageId($app, $imageId);

        return new BinaryFileResponse($full_name);
    }


    function isImage($path)
    {
        $image_type = exif_imagetype($path);
        if(in_array($image_type , array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG))) {
            return true;
        }
        return false;
    }

    private function getCaptchaResponse(ParameterBag $post_bag, Application $app)
    {
        if($app['env'] == 'prod') {
            // validate captcha code
            if ($post_bag->has('grecaptcha')) {
                $grecaptcha = $post_bag->get('grecaptcha');
            }

            if (!empty($grecaptcha)) {
                $verifyData = array('response' => $grecaptcha);
                $capthcaValidationResponse = json_decode($app['captcha.service']->verify($verifyData));
            } else {
                // We have an error
                $error = new UnauthorizedError("Not a valid captcha code.");
                return new JsonResponse($error->toArray(), $error->getStatusCode());
            }

            if ($capthcaValidationResponse->success != true) {
                $error = new UnauthorizedError("Not a valid captcha code.");
                return new JsonResponse($error->toArray(), $error->getStatusCode());
            }
        }

        return null;
    }
}
