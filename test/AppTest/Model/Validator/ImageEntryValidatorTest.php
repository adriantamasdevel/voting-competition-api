<?php

namespace AppTest\Model\Validator;

use App\Model\Entity\Competition;
use App\Model\Entity\ImageEntry;
use App\Model\Validator\ImageEntryValidator;

class ImageEntryValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $firstName
     * @param $lastName
     * @param $email
     * @param $description
     * @return ImageEntry
     */
    private static function createImageEntry(
        $firstName,
        $lastName,
        $email,
        $description
    ) {
        return new ImageEntry(
            1, //$imageId,
            $firstName,
            $lastName,
            $email,
            $description,
            ImageEntry::STATUS_UNMODERATED, //$status,
            new \DateTime(), // $dateSubmitted,
            "", //$ipAddress,
            "jpg", //$imageExtension,
            1 //$competitionId
        );
    }

    public function testOk()
    {
        $imageEntry = $this->createImageEntry("Adrian", "Tamas", "test@example.com", "This is a test image");
        $validator = new ImageEntryValidator();
        $this->assertFalse($validator->hasErrors($imageEntry));
    }

    public function testMissingFirstName()
    {
        $imageEntry = $this->createImageEntry("", "Tamas", "test@example.com", "This is a test image");
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }

    public function testMissingLastName()
    {
        $imageEntry = $this->createImageEntry("Adrian", "", "test@example.com", "This is a test image");
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }

    public function testMissingEmail()
    {
        $imageEntry = $this->createImageEntry("Adrian", "Tamas", "", "This is a test image");
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }

    public function testMissingDescription()
    {
        $imageEntry = $this->createImageEntry("Adrian", "Tamas", "test@example.com", "");
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }

    public function testTooLongFirstName()
    {
        $imageEntry = $this->createImageEntry(
            str_repeat("a", ImageEntry::FIRST_NAME_MAX_LENGTH + 1),
            "Tamas",
            "test@example.com",
            "This is a test image"
        );
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }

    public function testTooLongLastName()
    {
        $imageEntry = $this->createImageEntry(
            "Adrian",
            str_repeat("a", ImageEntry::LAST_NAME_MAX_LENGTH + 1),
            "test@example.com",
            "This is a test image"
        );
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }

    public function testTooLongEmail()
    {
        $imageEntry = $this->createImageEntry(
            "Adrian",
            "Tamas",
            str_repeat("a", ImageEntry::EMAIL_MAX_LENGTH + 1),
            "This is a test image"
        );
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }

    public function testTooLongDescription()
    {
        $imageEntry = $this->createImageEntry(
            "Adrian",
            "Tamas",
            "test@example.com",
            str_repeat("a", ImageEntry::DESCRIPTION_MAX_LENGTH + 1)
        );
        $validator = new ImageEntryValidator();
        $this->assertTrue($validator->hasErrors($imageEntry));
    }
}
