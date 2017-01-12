<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use App\Exception\InvalidApiValueException;
use App\Model\Entity\Competition;
use App\Model\Entity\ImageEntry;
use App\VariableMap\ArrayVariableMap;

class ApiParams
{
    const MAX_DIGITS = 15;

    const MAX_UUID_CHARS = 36;
    const MAX_LIMIT_VALUE = 1000;
    const MAX_OFFSET_VALUE = 9999999999999999;
    const MAX_INDEX_VALUE = 9999999999999999;

    const SORT_ALLOWED_CHARS = '\-a-zA-Z\,';
    const UUID_ALLOWED_CHARS = 'a-zA-Z0-9-';

    const ERROR_VOTING_IS_NOT_OPEN_FOR_COMPETITION = 120;
    const ERROR_VOTE_FROM_IP_ADDRESS_EXISTS = 121;
    const ERROR_IMAGE_ENTRY_NOT_OPEN_FOR_COMPETITION = 122;
    const ERROR_FORM_ERRORS = 123;


    /** @var VariableMap */
    private $variableMap;

    /** @var RouteParams  */
    private $routeParams;

    public function __construct(VariableMap $variableMap, RouteParams $routeParams)
    {
        $this->variableMap = $variableMap;
        $this->routeParams = $routeParams;
    }

    public static function fromArray(array $array)
    {
        $varMap = new ArrayVariableMap($array);
        $routeParams = new RouteParams([]);

        return new self($varMap, $routeParams);
    }

    public function getRandomToken()
    {
        $renewRandomToken = $this->variableMap->getVariable('renewRandomToken', false);
        if ($renewRandomToken !== false && $renewRandomToken !== 'false') {
            return '';
        }

        return $this->getStringValue('randomToken', 512, '');
    }

    public function getImageEntryStatusFilter()
    {
        return $this->getStringValue('statusFilter', 128, '');
    }

    public function getCompetitionIdFilter()
    {
        return $this->getStringValue('competitionIdFilter', 1024, '');
    }

    public function getLimit($default = 20)
    {
        return $this->getIntegerValue('limit', $default);
    }

    public function getOffset($default = 0)
    {
        return $this->getIntegerValue('offset', $default, self::MAX_OFFSET_VALUE);
    }

    public function getCompetitionId()
    {
        return $this->getId('competitionId');
    }

    public function getImageWidth()
    {
       return $this->getIntegerValue('imageWidth', null);
    }

    public function hasCompetitionStatus()
    {
        $value = $this->getVariable('status', null);
        if ($value === null) {
            return false;
        }

        return true;
    }

    public function getCompetitionStatus()
    {
        $status = $this->getStringValue('status', 128, null);
        Competition::assertIsKnownCompetitionStatus($status);

        return $status;
    }

    public function hasCompetitionDescription()
    {
        $value = $this->getVariable('description', null);
        if ($value === null) {
            return false;
        }

        return true;
    }

    public function getCompetitionDescription()
    {
        return $this->getStringValue('description', Competition::DESCRIPTION_MAX_LENGTH, null);
    }

    public function hasCompetitionTitle()
    {
        $value = $this->getVariable('title', null);
        if ($value === null) {
            return false;
        }

        return true;
    }

    public function getCompetitionTitle()
    {
        return $this->getStringValue('title', Competition::TITLE_MAX_LENGTH, null);
    }

    public function getImageId()
    {
        return $this->getUuidValue('imageId');
    }

    public function getImageEntryId()
    {
        return $this->getUuidValue('imageId');
    }

    public function getFirstName()
    {
        return $this->getStringValue('firstName', 1024, null);
    }

    public function getLastName()
    {
        return $this->getStringValue('lastName', 1024, null);
    }

    public function getUserEmail()
    {
        return $this->getStringValue('email', 4096, null);
    }

    public function getThirdPartyOptIn()
    {
        return $this->getBooleanValue('thirdPartyOptIn', false);
    }

    public function getImageDescription()
    {
        return $this->getStringValue('description', 10240, null);
    }

    public function getImageStatus()
    {
        $status = $this->getStringValue('status', 128, null);
        ImageEntry::assertIsKnownStatus($status);

        return $status;
    }

    public function getSort()
    {
        $value = $this->getVariable('sort', null);
        if (preg_match('#[^'.self::SORT_ALLOWED_CHARS.']#', $value) !== 0) {
            throw InvalidApiValueException::validChars('sort', self::SORT_ALLOWED_CHARS);
        }

        if ($value === null) {
            return null;
        }

        return explode(',', $value);
    }

    public function hasFirstName()
    {
        $value = $this->getVariable('firstName', null);
        if ($value === null) {
            return false;
        }
        return true;
    }

    public function hasLastName()
    {
        $value = $this->getVariable('lastName', null);
        if ($value === null) {
            return false;
        }
        return true;
    }

    public function hasUserEmail()
    {
        $value = $this->getVariable('email', null);
        if ($value === null) {
            return false;
        }
        return true;
    }

    public function hasImageDescription()
    {
        $value = $this->getVariable('description', null);
        if ($value === null) {
            return false;
        }
        return true;
    }

    public function hasStatus()
    {
        $value = $this->getVariable('status', null);
        if ($value === null) {
            return false;
        }
        return true;
    }


    public function hasDateEntriesClose()
    {
        $value = $this->getVariable('dateEntriesClose', null);
        if ($value === null) {
            return false;
        }

        return true;
    }

    public function getDateEntriesClose()
    {
        $string =  $this->getStringValue('dateEntriesClose', 128, null);

        return $this->createDateTime('dateEntriesClose', $string);
    }

    public function hasDateVotesClose()
    {
        $value = $this->getVariable('dateVotesClose', null);
        if ($value === null) {
            return false;
        }

        return true;
    }

    private function createDateTime($name, $stringValue)
    {
        $result = \DateTime::createFromFormat(\DateTime::ISO8601, $stringValue);
        if (!$result instanceof \DateTime) {
            throw new InvalidApiValueException("$name appears to not be a valid ISO8601 date");
        }

        $twoYearsInFuture = new \DateTime();
        $twoYearsInFuture->add(new \DateInterval('P2Y'));

        $twoYearsInPast = new \DateTime();
        $twoYearsInPast->sub(new \DateInterval('P2Y'));

        if ($result > $twoYearsInFuture) {
            throw new InvalidApiValueException("$name is too far in the future, maximum is two years from now.");
        }

        if ($result < $twoYearsInPast) {
            throw new InvalidApiValueException("$name is too far in the past, maximum is two years ago.");
        }

        return $result;
    }


    public function getDateVotesClose()
    {
        $string =  $this->getStringValue('dateVotesClose', 128, null);

        return $this->createDateTime('dateVotesClose', $string);
    }

    public function hasInitialStatusOfImages()
    {
        $value = $this->getVariable('initialStatusOfImages', null);
        if ($value === null) {
            return false;
        }

        return true;
    }

    public function getInitialStatusOfImages()
    {
        $value = $this->getStringValue('initialStatusOfImages', 128, null);
        ImageEntry::assertIsKnownStatus($value);

        return $value;
    }

    /**
     * No particular reason for this to be private - it's only private for now to reduce the
     * public api size.
     * @param $paramName
     * @param $default
     * @param int $maximumValue
     * @return int
     * @throws InvalidApiValueException
     */
    private function getIntegerValue($paramName, $default, $maximumValue = self::MAX_LIMIT_VALUE)
    {
        $value = $this->getVariable($paramName, $default);

        if (preg_match('#[^\d]#', $value) !== 0) {
            throw InvalidApiValueException::onlyDigits($paramName);
        }

        // $value is only composed of digits
        // Do string length check to complete avoid overflow errors.
        if (strlen($value) > self::MAX_DIGITS) {
            throw InvalidApiValueException::tooManyDigits($paramName, self::MAX_DIGITS);
        }

        // $value is only composed of digits and less than self::MAX_DIGITS digits long
        $intValue = intval($value);
        if ($intValue > $maximumValue) {
            throw InvalidApiValueException::tooLarge($paramName, $maximumValue);
        }

        return $intValue;
    }

    /**
     * @param $paramName
     * @param $maxLength
     * @param $default
     * @return bool|mixed
     * @throws \App\Exception\InvalidApiValueException
     */
    private function getStringValue($paramName, $maxLength, $default)
    {
        $value = $this->getVariable($paramName, $default);
        if ($value === null) {
            throw InvalidApiValueException::missing($paramName);
        }

        if (strlen($value) > $maxLength) {
            throw InvalidApiValueException::tooLong($paramName, $maxLength);
        }

        // TODO - check for UTF-8 chars

        return $value;
    }


    /**
     * @param $paramName
     * @param $default
     * @return bool|mixed
     * @throws \App\Exception\InvalidApiValueException
     */
    private function getBooleanValue($paramName, $default)
    {
        $value = $this->getVariable($paramName, $default);
        if ($value === null) {
            throw InvalidApiValueException::missing($paramName);
        }

        // the value will be a string if it comes from the request, or a boolean if
        // it is the default value.
        if ($value === 'false' || $value === false) {
            return false;
        }
        if ($value === 'true' || $value === true) {
            return true;
        }

        throw InvalidApiValueException::invalidBoolean();
    }


    /**
     * @param $paramName
     * @return bool|mixed
     * @throws \App\Exception\InvalidApiValueException
     */
    private function getUuidValue($paramName)
    {
        $value = $this->getVariable($paramName, null);

        if ($value === null) {
            throw InvalidApiValueException::missing($paramName);
        }

        // Do string length check to complete avoid overflow errors.
        if (strlen($value) > self::MAX_UUID_CHARS) {
            throw InvalidApiValueException::tooLong($paramName, 36);
        }
        else if (strlen($value) < self::MAX_UUID_CHARS) {
            throw InvalidApiValueException::tooShort($paramName, 36);
        }

        if (preg_match('#[^'.self::UUID_ALLOWED_CHARS.']#', $value) !== 0) {
            throw InvalidApiValueException::validChars($paramName, self::UUID_ALLOWED_CHARS);
        }

        return $value;
    }

    private function getVariable($variableName, $default)
    {
        if ($this->routeParams->hasParam($variableName) === true) {
            return $this->routeParams->getVariable($variableName, $default);
        }

        return $this->variableMap->getVariable($variableName, $default);
    }


    private function getId($idName)
    {
        $value = $this->getVariable($idName, null);

        if ($value === null) {
            throw InvalidApiValueException::missing($idName);
        }

        if (preg_match('#[^\d]#', $value) !== 0) {
            throw InvalidApiValueException::onlyDigits($idName);
        }

        // $value is only composed of digits
        // Do string length check to complete avoid overflow errors.
        if (strlen($value) > strlen(self::MAX_INDEX_VALUE)) {
            throw InvalidApiValueException::tooLarge($idName, self::MAX_LIMIT_VALUE);
        }

        return intval($value);
    }
}
