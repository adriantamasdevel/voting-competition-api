<?php

namespace App\Model\Entity;

use App\Exception\InvalidApiValueException;

/**
 * @SWG\Definition(
 *     definition="imageEntry",
 * )
 */
class ImageEntry
{
    const STATUS_UNMODERATED = 'STATUS_UNMODERATED';
    const STATUS_VERIFIED    = 'STATUS_VERIFIED';
    const STATUS_HIDDEN      = 'STATUS_HIDDEN';
    const STATUS_BLOCKED     = 'STATUS_BLOCKED';

    const EMAIL_MAX_LENGTH = 256;
    const DESCRIPTION_MAX_LENGTH = 2048;
    const FIRST_NAME_MAX_LENGTH = 70;
    const LAST_NAME_MAX_LENGTH = 70;

    public static $knownStatuses = [
        self::STATUS_UNMODERATED,
        self::STATUS_VERIFIED,
        self::STATUS_HIDDEN,
        self::STATUS_BLOCKED,
    ];

    public static function assertIsKnownStatus($status)
    {
        if (in_array($status, ImageEntry::$knownStatuses) == false) {
            throw new InvalidApiValueException("Unknown status type '$status'");
        }
    }


    /**
     * Primary key
     *
     * @var string $imageId
     * @SWG\Property(type="string")
     */
    protected $imageId;

    /**
     * Competition id
     *
     * @var string $competitionId
     * @SWG\Property(type="string")
     */
    protected $competitionId;


    /**
     * User first name
     *
     * @var string $firstName
     * @SWG\Property(type="string")
     */
    protected $firstName;

    /**
     * User last name
     *
     * @var string $lastName
     * @SWG\Property(type="string")
     */
    protected $lastName;

    /**
     * User email
     *
     * @var string $email
     * @SWG\Property(type="string")
     */
    protected $email;

    /**
     * Image description
     *
     * @var string $description
     * @SWG\Property(type="string")
     */
    protected $description;

    /**
     * Image status
     *
     * @var string $status can be STATUS_UNMODERATE, STATUS_VERIFIED, STATUS_HIDDEN, STATUS_BLOCKED,
     * @SWG\Property(type="string")
     */
    protected $status;

    /**
     * Image full url
     *
     * @var string $imageURL
     * @SWG\Property(type="string")
     */
    protected $imageURL;

    /**
     * Date of submission
     *
     * @var \DateTime $dateSubmitted
     * @SWG\Property(type="integer")
     */
    protected $dateSubmitted;

    /**
     * User $ipAddress
     *
     * @var int $ipAddress
     * @SWG\Property(type="integer")
     */
    protected $ipAddress;

    /**
     * Image Extension
     *
     * @var string $imageExtension
     * @SWG\Property(type="string")
     */
    protected $imageExtension;


    /**
     * @var boolean
     * @SWG\Property(type="boolean")
     */
    private $thirdPartyOptIn;


    public function __construct(
        $imageId,
        $firstName,
        $lastName,
        $email,
        $description,
        $status,
        \DateTime $dateSubmitted,
        $ipAddress,
        $imageExtension,
        $competitionId,
        $thirdPartyOptIn
    ) {
        $this->imageId = $imageId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->description = $description;
        $this->status = $status;
        $this->dateSubmitted = $dateSubmitted;
        $this->ipAddress = $ipAddress;
        $this->imageExtension = $imageExtension;
        $this->competitionId = $competitionId;
        $this->thirdPartyOptIn = $thirdPartyOptIn;
    }

    public static function fromArray($data)
    {
        $imageExtension = null;
        if (array_key_exists('imageExtension', $data)) {
            $imageExtension = $data['imageExtension'];
        }

        $instance = new self(
            $data['imageId'],
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['description'],
            $data['status'],
            new \DateTime($data['dateSubmitted']),
            $data['ipAddress'],
            $imageExtension,
            $data['competitionId'],
            $data['thirdPartyOptIn']
        );

        return $instance;
    }

    public function toArray($includeRestrictedData, $hostAndPath, $imageWidth)
    {
        $data = [];
        $data['imageId'] = $this->imageId;
        $data['description'] = $this->description;
        $data['dateSubmitted'] = $this->dateSubmitted->format(\DateTime::ISO8601);
        $data['competitionId'] = $this->competitionId;
        
        if ($includeRestrictedData) {
            $data['firstName'] = $this->firstName;
            $data['lastName'] = $this->lastName;
            $data['email'] = $this->email;
            $data['status'] = $this->status;
            $data['ipAddress'] = $this->ipAddress;
            $data['thirdPartyOptIn'] = $this->thirdPartyOptIn;
        }

        if ($hostAndPath === null) {
            $data['imageURL'] = $this->imageId;
        }
        else {
            $data['imageURL'] = $hostAndPath . 'img_' . $this->imageId. '-' . $imageWidth . '-95.'.$this->imageExtension;
        }

        return $data;
    }

    public function toDbData()
    {
        $data = [];
        $data['image_id'] = $this->imageId;
        $data['first_name'] = $this->firstName;
        $data['last_name'] = $this->lastName;
        $data['email'] = $this->email;
        $data['description'] = $this->description;
        $data['status'] = $this->status;
        $data['date_submitted'] = $this->dateSubmitted->format('Y-m-d H:i:s');
        $data['ip_address'] = $this->ipAddress;
        $data['image_extension'] = $this->imageExtension;
        $data['competition_id'] = $this->competitionId;
        if ($this->thirdPartyOptIn) {
            $data['third_party_opt_in'] = 1;
        }
        else {
            $data['third_party_opt_in'] = 0;
        }

        return $data;
    }

    public static function fromDbData($data)
    {
        $instance = new self(
            $data['image_id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['description'],
            $data['status'],
            new \DateTime($data['date_submitted']),
            $data['ip_address'],
            $data['image_extension'],
            $data['competition_id'],
            $data['third_party_opt_in']
        );

        return $instance;
    }

    /**
     * @return string
     */
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * @return string
     */
    public function getCompetitionId()
    {
        return $this->competitionId;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getImageURL()
    {
        return $this->imageURL;
    }

    /**
     * @return string
     */
    public function getDateSubmitted()
    {
        return $this->dateSubmitted;
    }

    /**
     * @return int
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @return boolean
     */
    public function getThirdPartyOptIn()
    {
        return $this->thirdPartyOptIn;
    }
}
