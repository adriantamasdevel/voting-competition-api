<?php

namespace App\Repo\Mock;

use App\Model\Entity\ImageEntry;
use App\Model\Entity\Competition;
use App\Model\Entity\ImageEntryWithScore;
use App\Order\ImageEntryWithScoreOrder;
use App\Repo\ImageEntryWithScoreRepo;
use App\Model\Entity\ImageEntryPatch;
use Ramsey\Uuid\Uuid;
use App\Model\Filter\ImageEntryWithScoreFilter;

class ImageEntryWithScoreMockRepo implements ImageEntryWithScoreRepo
{

    public function getImageEntryWithScore($imageEntryId)
    {
        return ImageEntryWithScore::fromDbData(self::getDbData());
    }

    public function getTotalImageEntries(ImageEntryWithScoreFilter $imageEntryWithScoreFilter)
    {
        return 20;
    }

    /**
     * @param $offset
     * @param $limit
     * @param ImageEntryWithScoreOrder $imageEntryWithScoreOrder
     * @param ImageEntryWithScoreFilter $imageEntryWithScoreFilter
     * @return ImageEntryWithScore[]
     */
    public function getImageEntriesWithScore(
        $offset, $limit,
        ImageEntryWithScoreOrder $imageEntryWithScoreOrder,
        ImageEntryWithScoreFilter $imageEntryWithScoreFilter
    ) {
        $imageInfoArray = [];
        $imageInfoArray[] = ImageEntryWithScore::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntryWithScore::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntryWithScore::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntryWithScore::fromDbData(self::getDbData());
        $imageInfoArray[] = ImageEntryWithScore::fromDbData(self::getDbData());

        return $imageInfoArray;
    }


    public static function getDbData()
    {
        $data = [];
        $data["image_id"] = Uuid::uuid4()->toString();
        $data["firstName"] = "Adrian";
        $data["lastName"] = "Tamas";
        $data["email"] = "Adrian.Tamas@example.com";
        $data["description"] = "Some description of what the image is";
        $data["image_id"] = "123456";
        $data["competition_id"] = "2016-05-24_123";
        $data["status"] = "STATUS_APPROVED";
        $data["ip_address"] = "1.2.3.4";
        $data["imageURL"] = "http://c8.staticflickr.com/7/6139/5966639423_0949940efd_z.jpg";
        $data["date_submitted"] = "2016-06-08T14:00:28+00:00";
        $data["score"] = rand(5, 10);

        return $data;
    }
}
