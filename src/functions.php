<?php

use App\ApiParams;
use App\Model\Entity\ImageEntry;
use App\Exception\AuthenticationRequiredException;
use App\Exception\ContentNotFoundException;
use App\Exception\ImageAlreadyEnteredException;
use App\Exception\ImageEntryClosedException;
use App\Exception\InvalidApiValueException;
use App\Exception\VotingClosedException;
use App\Exception\UnknownImageException;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Finder\Finder;
use Ramsey\Uuid\Uuid;
use App\Model\Entity\Competition;
use Knp\Command\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;


function parseImageEntryStatusFilter($imageEntryStatusFilter)
{
    $allowedStatuses = null;
    $imageEntryStatusFilter = trim($imageEntryStatusFilter);
    if (mb_strlen($imageEntryStatusFilter) > 0) {
        $statuses = explode(',', $imageEntryStatusFilter);
        foreach ($statuses as $status) {
            $status = trim($status);
            ImageEntry::assertIsKnownStatus($status);
            $allowedStatuses[] = $status;
        }
    }

    return $allowedStatuses;
}

function parseCompetitionStatusFilter($competitionStatusFilter)
{
    $allowedStatuses = null;
    $competitionStatusFilter = trim($competitionStatusFilter);
    if (mb_strlen($competitionStatusFilter) > 0) {
        $statuses = explode(',', $competitionStatusFilter);
        foreach ($statuses as $status) {
            $status = trim($status);
            Competition::assertIsKnownCompetitionStatus($status);
            $allowedStatuses[] = $status;
        }
    }

    return $allowedStatuses;
}


function parseCompetitionIdFilter($competitionIdFilter)
{
    $allowedCompetitionIds = null;
    $competitionIdFilter = trim($competitionIdFilter);

    if (mb_strlen($competitionIdFilter) > 0) {
        $competitionIds = explode(',', $competitionIdFilter);
        foreach ($competitionIds as $competitionId) {
            $competitionId = trim($competitionId);
            //@TODO - validation of values
            $allowedCompetitionIds[] = $competitionId;
        }
    }

    return $allowedCompetitionIds;
}

function createJsonErrorResponse($status, $message, $userMessage = null, $errorCode = null, $extraInfo = null)
{
    $data = [
        "StatusCode" => $status,
        "DeveloperMessage" => $message,
        "UserMessage" => $userMessage,
        "ErrorCode" => $errorCode,
    ];

    if ($extraInfo !== null) {
        $data = array_merge($data, $extraInfo);
    }

    return new JsonResponse(
        $data,
        $status
    );
}

/**
 * Catch known exceptions and convert them to standard errors.
 *
 * @param Exception $e
 * @return null|JsonResponse
 */
function createErrorResponseFromException(\Exception $e)
{
    if ($e instanceof InvalidApiValueException) {
        return createJsonErrorResponse(400, $e->getMessage());
    }

    if ($e instanceof UnknownImageException ||
        $e instanceof ContentNotFoundException) {
        return createJsonErrorResponse(404, $e->getMessage());
    }

    if ($e instanceof ImageAlreadyEnteredException) {
        return createJsonErrorResponse(409, $e->getMessage());
    }

    if ($e instanceof AuthenticationRequiredException) {
        return createJsonErrorResponse(403, $e->getMessage());
    }

    return null;
}

function getImageExtension(Application $app, $imageId){

    try {
        $fileFound = findFileByImageId($app, $imageId);
        $path_parts = pathinfo($fileFound);

        return $path_parts['extension'];

    } catch (ContentNotFoundException $cnfe) {
        return '';
    }

}


function findFileByImageId(Application $app, $imageId)
{
    $storage_path = $app['image.upload_path'];

    $finder = new Finder();
    $files = $finder->files()->in($storage_path)->name('img_' . $imageId. '*');

    if ($files->count() > 0) {
        foreach ($files as $file) {
            return $storage_path . $file->getRelativePathname();
        }
    }

    throw new ContentNotFoundException("Image not found.");
}


function safeText($string)
{
    htmlentities($string, ENT_COMPAT | ENT_HTML401, "UTF-8");
}

/**
 * Creates a random IP address that is 'guaranteed' to not be one used by a real user,
 * as the values are from the restricted range.
 * @return string
 */
function createRandomIpAddress()
{
    $ipAddress = "10.".rand(1,255).".".rand(1,255).".".rand(1,255);

    return $ipAddress;
}


/**
 * Creates an  IP address  - the ip's generated increment, and start at 10.0.0.0 each
 * script run.
 * @return string
 */
function createIncrementingIpAddress()
{
    static $count = 0;

    $low = $count & 0xff;
    $mid = ($count & 0xff00) >> 8;
    $high = ($count & 0xff0000) >> 16;

    $count++;
    $ipAddress = "10.".$high.".".$mid.".".$low;

    return $ipAddress;
}


/**
 * @param $competitionId
 * @param $extension
 * @param $srcImagePath
 * @param $storage_path
 * @return string The unique ID of the image.
 * @throws Exception
 */
function saveImageFile($extension, $srcImagePath, $storage_path)
{
    $maxAttempts = 10;

    for ($i=0; $i<$maxAttempts; $i++) {
        $imageUniqueId = Uuid::uuid4()->toString();
        $new_name = 'img_' . $imageUniqueId;

        // save the file to storage
        // @TODO - make this not have a race condition...
        file_put_contents(
            $storage_path . $new_name . '.' . $extension,
            file_get_contents($srcImagePath)
        );

        return $imageUniqueId;
    }

    throw new \Exception("Failed to find unique file name.");
}


function setExceptionErrorHandler()
{
    $errorHandler = function ($errorNumber, $errorMessage, $errorFile, $errorLine)
    {
        if (error_reporting() === 0) {
            // Error reporting has be silenced
            return true;
        }
        if ($errorNumber === E_DEPRECATED) {
            return true; //Don't care - deprecated warnings are generally not useful
        }

        if ($errorNumber === E_CORE_ERROR || $errorNumber === E_ERROR) {
            // For these two types, PHP is shutting down anyway. Return false
            // to allow shutdown to continue
            return false;
        }

        $message = "Error: [$errorNumber] $errorMessage in file $errorFile on line $errorLine<br />\n";
        throw new \Exception($message);
    };

    set_error_handler($errorHandler);
}


function showRawCharacters($result) {
    $resultInHex = unpack('H*', $result);
    $resultInHex = $resultInHex[1];
    $resultSeparated = implode(', ', str_split($resultInHex, 2)); //byte safe
    echo $resultSeparated;
}

function assertVotingStillOpen(Competition $competition, \DateTime $currentTime = null)
{
    if ($currentTime === null) {
        $currentTime = new \DateTime();
    }

    if ($currentTime > $competition->getDateVotesClose() ||
        ($competition->getStatus() !== Competition::STATUS_OPEN &&
         $competition->getStatus() !== Competition::STATUS_VOTING)) {
        throw new VotingClosedException("Voting for competition ".$competition->getCompetitionId()." is not open.");
    }
}

function assertImageEntryStillOpen(Competition $competition, \DateTime $currentTime = null)
{
    if ($currentTime === null) {
        $currentTime = new \DateTime();
    }

    if ($currentTime > $competition->getDateEntriesClose() ||
        ($competition->getStatus() !== Competition::STATUS_OPEN)) {
        throw new ImageEntryClosedException("Image entry for competition ".$competition->getCompetitionId()." has closed.");
    }
}
