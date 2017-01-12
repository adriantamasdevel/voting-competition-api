<?php

namespace App\Command;
use Silex\Application;
use Knp\Command\Command;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use App\Repo\ImageEntryRepo;
use App\ApiParams;
use App\Order\ImageEntryOrder;
use App\Model\Filter\ImageEntryFilter;
use App\Model\Entity\ImageEntry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class CronImagesToModerate extends \Knp\Command\Command {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setDescription("Cron task to notify moderate when there are images to moderate.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        /** @var $imageEntryRepo \App\Repo\ImageEntryRepo */
        $imageEntryRepo = $app[ImageEntryRepo::class];

        $imageEntryOrder = ImageEntryOrder::fromArray([]);
        $imageEntryFilter = ImageEntryFilter::createByStatus([ImageEntry::STATUS_UNMODERATED]);

        $images = $imageEntryRepo->getImageEntries(0, 100, $imageEntryOrder, $imageEntryFilter);
        $waiting_images = count($images);

        if($waiting_images > 0 )
        {
            $ii = ($waiting_images > 1) ? 's':'';

            $payload = array(
                "username" => "localhost Image Comp. Notifications",
                "icon_emoji" => ":frame_with_picture:",
                "attachments" => array(
                    array(
                        "fallback" => $waiting_images." new image".$ii." waiting for approval",

                        "title" => $waiting_images." new image".$ii." waiting for approval",
                        "title_link" => $app['admin.base_url'],
                        "color" => "#36a64f",
                        "fields" => array(
                            array(
                                "title" =>  "Env: ".$app['env'],
                                "value" =>  "",
                                "short" =>  true
                            )
                        ),
                        "ts" => time()
                    )
                )
            );

            $slackMessage = array('payload' => json_encode($payload));
            $app['slack.service']->send($slackMessage);
        }
        echo "No waiting images.\n";

        exit(0);
    }
}
