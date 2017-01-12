<?php

namespace App\Model\Validator;

use App\Model\Entity\ImageEntry;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;


class ImageEntryValidator
{

    const M_ERROR_FIRST_NAME_MISSING = "Please enter a first name";
    const M_ERROR_FIRST_NAME_TOO_LONG = "First name too long, maximum length is %d";

    const M_ERROR_LAST_NAME_MISSING = "Please enter a last name";
    const M_ERROR_LAST_NAME_TOO_LONG = "Last name too long, maximum length is %d";

    const M_ERROR_EMAIL_TOO_LONG = "Email too long, maximum length is %d";
    const M_ERROR_EMAIL_INVALID = "Please enter a valid email address";

    const M_ERROR_DESCRIPTION_MISSING = "Please enter a description";
    const M_ERROR_DESCRIPTION_TOO_LONG = "Description too long, maximum length is %d";

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    public function __construct()
    {
        $this->emailValidator = new EmailValidator();
    }

    public function hasErrors(ImageEntry $imageEntry)
    {
        $errors = $this->getErrors($imageEntry);
        if (count($errors) > 0) {
            return true;
        }

        return false;
    }

    public function getErrors(ImageEntry $imageEntry)
    {
        $errors = [];

        // @TODO - adding a DNS lookup check is trivial, but out of scope
        // $validationRule = new MultipleValidationWithAnd([
        //     new RFCValidation(),
        //     new DNSCheckValidation()
        // ]);
        $validationRule = new RFCValidation();


        if (mb_strlen($imageEntry->getEmail()) > ImageEntry::EMAIL_MAX_LENGTH) {
            $errors[] = [
                "name" => "/email",
                "reason" => sprintf(self::M_ERROR_EMAIL_TOO_LONG, ImageEntry::EMAIL_MAX_LENGTH)
            ];
        }
        //If it's an invalid email address,
        else if ($this->emailValidator->isValid($imageEntry->getEmail(), $validationRule) !== true ||
        // or it starts with "@example.com", reject it.
            stripos(strrev($imageEntry->getEmail()), strrev("@example.com")) === 0) {
            $errors[] = [
                "name" => "/email",
                "reason" => self::M_ERROR_EMAIL_INVALID
            ];
        }

        // First name
        if (strlen($imageEntry->getFirstName()) == 0) {
            $errors[] = [
                "name" => "/firstName",
                "reason" => self::M_ERROR_FIRST_NAME_MISSING
            ];
        }
        if (mb_strlen($imageEntry->getFirstName()) > ImageEntry::FIRST_NAME_MAX_LENGTH) {
            $errors[] = [
                "name" => "/firstName",
                "reason" => sprintf(self::M_ERROR_FIRST_NAME_TOO_LONG, ImageEntry::FIRST_NAME_MAX_LENGTH)
            ];
        }

        // description
        if (strlen($imageEntry->getDescription()) == 0) {
            $errors[] = [
                "name" => "/description",
                "reason" => self::M_ERROR_DESCRIPTION_MISSING
            ];
        }
        if (mb_strlen($imageEntry->getDescription()) > ImageEntry::DESCRIPTION_MAX_LENGTH) {
            $errors[] = [
                "name" => "/description",
                "reason" => sprintf(self::M_ERROR_DESCRIPTION_TOO_LONG, ImageEntry::DESCRIPTION_MAX_LENGTH)
            ];
        }

        // lastName
        if (mb_strlen($imageEntry->getLastName()) == 0) {
            $errors[] = [
                "name" => "/lastName",
                "reason" =>  self::M_ERROR_LAST_NAME_MISSING
            ];
        }
        if (mb_strlen($imageEntry->getLastName()) > ImageEntry::LAST_NAME_MAX_LENGTH) {
            $errors[] = [
                "name" => "/lastName",
                "reason" => sprintf(self::M_ERROR_LAST_NAME_TOO_LONG, ImageEntry::LAST_NAME_MAX_LENGTH)
            ];
        }

        return $errors;
    }
}
