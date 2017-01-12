<?php

namespace App\Command;

use App\ApiParams;
use App\Model\Entity\Competition;
use App\Repo\CompetitionRepo;
use App\Repo\ImageEntryRepo;
use App\Repo\VoteRepo;
use App\Model\Entity\ImageEntry;
use Knp\Command\Command;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Silex\Application;



class NodeData implements \IteratorAggregate
{
    private $originalData;

    public function __construct(array $originalData)
    {
        $this->originalData = $originalData;
    }

    public function getIterator()
    {
        $data = [];

        foreach ($this->originalData['nodes'] as $node) {
            $node = $node['node'];
            $image = $node["Image"];
            $imageURL = str_replace('homepagethumb100x75.', '', $image);
            $path = parse_url($imageURL, PHP_URL_PATH);
            $localFilename = ROOT_PATH."/data/images/".basename($path);
            $newData = $node;
            $newData['imageURL'] = $imageURL;
            $newData['localFilename'] = $localFilename;

            $data[] = $newData;
        }

        return new \ArrayIterator($data);
    }
}



class ImportCompetition extends \Knp\Command\Command
{
    /** @var  \Silex\Application */
    private $app;

    public function __construct($name)
    {
        parent::__construct($name);

        $this->setDescription("Import a competition - from the photo-competition.json.");

        $this->addArgument(
            'competitionId',
            InputArgument::REQUIRED,
            'Which competition to update - digits only'
        );

        $this->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'The path of the json file to import'
        );

    }

    private function createImage($localFilename)
    {
        echo "Files downloaded.\n";
        $knownImageTypes = [
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
        ];

        $imageType = exif_imagetype($localFilename);
        if ($imageType === false) {
            echo "Failed to read file ".$localFilename."\n";
            exit(-1);
        }
        if (array_key_exists($imageType, $knownImageTypes) == false) {
            echo "Unsupported image type of $imageType for image ".$localFilename;
        }
        $extension = $knownImageTypes[$imageType];

        $uuid = saveImageFile($extension, $localFilename, $this->app['app.storage']);
        echo "Saved file to, $uuid\n";

        return [$uuid, $extension];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getSilexApplication();

        /** @var $imageEntryRepo ImageEntryRepo */
        $imageEntryRepo = $this->app[ImageEntryRepo::class];

        /** @var $voteRepo VoteRepo */
        $voteRepo = $this->app[VoteRepo::class];

        $apiParams = ApiParams::fromArray([
            'competitionId' => $input->getArgument('competitionId'),
        ]);

        $competitionId = $apiParams->getCompetitionId();

        $filename = $input->getArgument('filename');

        $json = @file_get_contents($filename);
        if ($json == false) {
            echo "Failed to read json data from $filename";
            exit(-1);
        }
        $data = json_decode($json, true);
        if (is_array($data) === false) {
            echo "Failed to parse json data from $filename";
            exit(-1);
        }

        $nodeData = new NodeData($data);
        foreach ($nodeData as $entry) {
            if (file_exists($entry['localFilename']) == false) {
                copy($entry['imageURL'], $entry['localFilename']);
            }
            echo "File is downloaded to ".$entry['localFilename']."\n";
        }

        foreach ($nodeData as $entry) {
            list($uuid, $extension) = $this->createImage($entry['localFilename']);
            $name = trim($entry["Name"]);
            $nameParts = explode(' ', $name);
            $firstName = $nameParts[0];
            $lastName = '';
            if (count($nameParts) > 0) {
                $lastName = implode(' ', array_slice($nameParts, 1));
            }

            $dateSubmitted = \DateTime::createFromFormat("U", $entry["Date"]);

            $email = 'test@example.com';

            if (array_key_exists("Email", $entry) == true) {
                $email = $entry["Email"];
            }

            $entry['localFilename'];

            $imageEntry = new ImageEntry(
                $uuid,
                $firstName,
                $lastName,
                $email,
                $entry["ImageTitle"],
                ImageEntry::STATUS_VERIFIED,
                $dateSubmitted,
                createIncrementingIpAddress(),
                $extension,
                $competitionId
            );

            echo "Adding entry for $name \n";
            $votesToAdd = $entry["Votes"];
            echo "Adding $votesToAdd votes: ";
            $imageEntryRepo->create($imageEntry);
            for ($x=0; $x<$votesToAdd; $x++) {
                echo ".";
                $voteRepo->addVote($uuid, createIncrementingIpAddress());
            }
            echo "\n";
        }
    }
}
