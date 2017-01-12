<?php

namespace App\Repo\Mock;

use App\Model\Entity\ImageEntry;
use App\Model\Entity\Competition;
use App\Model\Entity\ImageEntryWithScore;
use App\Order\ImageEntryOrder;
use App\Model\Filter\ImageEntryFilter;
use App\Repo\ImageEntryRepo;
use App\Model\Entity\ImageEntryPatch;
use Ramsey\Uuid\Uuid;

class ImageEntryMockRepo implements ImageEntryRepo
{
    /**
     * @param int $id The ID of the team.
     * @return ImageEntry
     */
    public function getImageEntry($id)
    {
        return ImageEntry::fromDbData(self::getDbData());
    }

    public function getTotalImageEntries(ImageEntryFilter $imageEntryFilter)
    {
        return 20;
    }

    /**
     * @param int $id
     * @return ImageEntryWithScore
     */
    public function getImageEntryWithScore($id)
    {
        $imageEntry = ImageEntry::fromDbData(self::getDbData());

        return new ImageEntryWithScore(4, $imageEntry);
    }

    /**
     * @param $offset
     * @param $limit
     * @param ImageEntryOrder $imageInfoOrder
     * @param ImageEntryFilter $imageEntryFilter
     * @return ImageEntry[]
     */
    public function getImageEntries(
        $offset,
        $limit,
        ImageEntryOrder $imageInfoOrder,
        ImageEntryFilter $imageEntryFilter
    ) {
        $imageInfoArray = [];
        $imageInfoArray[] = ImageEntry::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntry::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntry::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntry::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntry::fromDbData(self::getDbData());

        return $imageInfoArray;
    }

    public function getImageInfoTotal()
    {
        return 5;
    }

    public function create(ImageEntry $imageEntry)
    {
        return $imageEntry;
    }

    public function update($imageId, ImageEntryPatch $imageEntryPatch)
    {
    }

    public static function getDbData()
    {
        static $id = 123456;

        $id++;

        $data = [];
        $data["image_id"] = Uuid::uuid4()->toString();
        $data["firstName"] = "Adrian";
        $data["lastName"] = "Tamas";
        $data["email"] = "Adrian.Tamas@example.com";
        $data["description"] = "Some description of what the image is";
        $data["image_id"] = "".$id;
        $data["competition_id"] = "2016-05-24_123";
        $data["status"] = "STATUS_APPROVED";
        $data["ip_address"] = "1.2.3.4";
        $data["imageURL"] = "http://c8.staticflickr.com/7/6139/5966639423_0949940efd_z.jpg";
        $data["date_submitted"] = "2016-06-08T14:00:28+00:00";

        return $data;
    }
}
