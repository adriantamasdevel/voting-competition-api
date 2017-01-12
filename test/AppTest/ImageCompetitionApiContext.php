<?php

namespace AppTest;

use Amp\Artax\Client as ArtaxClient;
use Amp\Artax\Response;
use App\Model\Entity\Competition;
use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use AppTest\ImageCompApi;
use Behat\Gherkin\Node\TableNode;

class ImageCompetitionApiContext implements Context, SnippetAcceptingContext
{
    /** @var  ArtaxClient */
    public $client;

    /** @var Response */
    private $response;

    private $apiEndpoints = array();

    private $lastURI = 'no request';

    /** @var \App\Model\Entity\Competition */
    private $currentCompetition = null;

    private $imageIds = [];




    /**
     * @var \AppTest\ImageCompApi
     */
    private $api;

    public function __construct()
    {
        $this->init();
        $this->client = new ArtaxClient();
    }

    private function init()
    {
        $environment = 'dev';
        $urlSettings = [
            'live' => array(
            //    'home.localhost' => 'http://local.api.imagecompetition.localhost.com/',
            ),
            'dev' => array(
                'image.user.api' => 'http://local.api.imagecompetition.localhost.com',
                'image.admin.api' => 'http://local.admin.api.imagecompetition.localhost.com',
            ),
            'stage' => array(
                'image.user.api' => 'http://local.api.imagecompetition.localhost.com',
                'image.admin.api' => 'http://local.admin.api.imagecompetition.localhost.com',
            )
        ];

        $environmentSetting = getenv('TEST_ENV');
        if ($environmentSetting !== false && array_key_exists($environmentSetting, $urlSettings) == true) {
            $environment = $environmentSetting;
        }
        static $firstLoop = true;
        if ($firstLoop == true) {
            echo "Read value [$environmentSetting] from TEST_ENV, using environment [$environment]\n";
            $firstLoop = false;
        }
        $this->apiEndpoints = $urlSettings[$environment];

        $this->api = new ImageCompApi();
    }

    private function getURL($api, $path)
    {
        return $this->apiEndpoints[$api] . $path;
    }

    /**
     * @When I send a :method request to :api with path :uri
     */
    public function iSendARequestToWithPath($method, $api, $path)
    {
        $uri = $this->getURL($api, $path);
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod($method)
            ->setUri($uri);

        $promise = $this->client->request($request);
        $this->response = \Amp\wait($promise);
    }

    /**
     * @Then /^the response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($responseCode)
    {
        $message = sprintf(
            "Response status of %s from %s is not the expected value %s",
            $this->lastURI,
            $this->response->getStatus(),
            $responseCode
        );

        Assertion::eq(
            $responseCode,
            $this->response->getStatus(),
            $message
        );
    }

    /**
     * @Given /^the response should match schema "([^"]*)"$/
     */
    public function theResponseShouldMatchSchema($schemaName)
    {
        $body = $this->response->getBody();
        $data = json_decode_real($body, false);
        checkJsonSchema($data, $schemaName);
    }

    /**
     * @Given /^I create a competition$/
     */
    public function iCreateACompetition()
    {
        $twoWeeksInFuture = new \DateTime();
        $twoWeeksInFuture->add(new \DateInterval('P2W'));

        $data = [
          "title" => "This is the title",
          "description" => "This is the description",
          "dateEntriesClose" => $twoWeeksInFuture->format(\DateTime::ISO8601),
          "dateVotesClose" => $twoWeeksInFuture->format(\DateTime::ISO8601),
          "initialStatusOfImages" => "STATUS_VERIFIED",
          "status" => "STATUS_OPEN"
        ];

        $body = (new \Amp\Artax\FormBody);
        $body->addFields($data);

        $uri = $this->getURL('image.admin.api', '/v1/competitions');
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod('POST')
            ->setUri($uri)
            ->setBody($body);

        $promise = $this->client->request($request);
        $response = \Amp\wait($promise);
        /** @var $response \Amp\Artax\Response */
        $data = json_decode_real($response->getBody(), true);
        $this->currentCompetition = Competition::fromArray($data['data']['competition']);
    }

    /**
     * @Given /^I upload (\d+) image entries$/
     */
    public function iUploadImageEntries($numberOfEntries)
    {
        for ($i=0; $i<$numberOfEntries; $i++) {
            $this->uploadImageEntry();
        }
    }

    private function uploadImageEntry() {
        $body = new \Amp\Artax\FormBody();
        $body->addField('competitionId', $this->currentCompetition->getCompetitionId());
        $body->addFile('file', __DIR__ . "/../data/brexit_still_life.jpeg");

        $uri = $this->getURL('image.admin.api', '/v1/images');
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod('POST')
            ->setUri($uri)
            ->setBody($body);

        $promise = $this->client->request($request);
        $response = \Amp\wait($promise);
        /** @var $response \Amp\Artax\Response */
        $imageJsonObject = json_decode_real($response->getBody(), false);
        checkJsonSchema($imageJsonObject, "imageSchema.json");

        $imageObject = json_decode_real($response->getBody(), true);

        $imageId = $imageObject['data']['image']['imageId'];
        $data = [
            'firstName' => 'testing',
            'lastName' =>  'testing',
            'email' => 'test@example.org',
            'description' => 'image description',
            'competitionId' => $this->currentCompetition->getCompetitionId(),
            'imageId' => $imageId
        ];

        $body = new \Amp\Artax\FormBody();
        $body->addFields($data);

        $uri = $this->getURL('image.admin.api', '/v1/imageEntries');
        $request = (new \Amp\Artax\Request)
            ->setMethod('POST')
            ->setUri($uri)
            ->setBody($body);

        $promise = $this->client->request($request);
        $response = \Amp\wait($promise);

        $imageJsonObject = json_decode_real($response->getBody(), false);
        checkJsonSchema($imageJsonObject, "imageEntryPostSchema.json");
        $this->imageIds[] = $imageId;
    }

    /**
     * @When /^I get the images for the created competition$/
     */
    public function iGetTheImagesForTheCreatedCompetition()
    {
        $api = "image.user.api";
        $path = "/v1/imageEntries?competitionIdFilter=".$this->currentCompetition->getCompetitionId();

        $this->response = $this->makeGetRequest($api, $path);
    }

    /**
     * @param $api
     * @param $path
     * @return \Amp\Artax\Response
     * @throws Throwable
     */
    private function makeGetRequest($api, $path)
    {
        $method = 'GET';
        $uri = $this->getURL($api, $path);
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod($method)
            ->setUri($uri);

        $promise = $this->client->request($request);

        $response = \Amp\wait($promise);
        /** @var $response \Amp\Artax\Response */
        Assertion::eq(200, $response->getStatus());

        return $response;
    }


    /**
     * @Given /^the last response should contain (\d+) images$/
     */
    public function theLastResponseShouldContainImages($numberOfImages)
    {

    }

    /**
     * @Given /^the last response should contain the created images$/
     */
    public function theLastResponseShouldContainTheCreatedImages()
    {
        $data = json_decode_real($this->response->getBody(), true);
        Assertion::eq(count($this->imageIds), count($data['data']['imageEntries']));

        $extractImageIdsFn = function ($imageEntry) {
            return $imageEntry['imageId'];
        };

        $this->checkElementsPresent($this->imageIds, array_map($extractImageIdsFn, $data['data']['imageEntries']));
    }

    private function checkElementsPresent($expectedElements, $actualElements)
    {
        $elementsFound = [];
        foreach ($expectedElements as $expectedElement) {
            $elementsFound[$expectedElement] = false;
        }
        foreach ($actualElements as $actualElement) {
            $elementsFound[$actualElement] = true;
        }

        foreach ($elementsFound as $expectedElement => $imageFound) {
            if ($imageFound == false) {
                throw new \Exception("Failed to find image $expectedElement in imageEntries");
            }
        }
    }


    /**
     * @When /^I test the random order, it should behave correctly$/
     */
    public function iTestTheRandomOrderItShouldBehaveCorrectly()
    {
        $api = "image.user.api";
        $path1 = sprintf(
            "/v1/imageEntries?competitionIdFilter=%d&sort=rand&limit=2",
            $this->currentCompetition->getCompetitionId()
        );
        $this->response = $this->makeGetRequest($api, $path1);
        checkResponseAgainstSchema($this->response, 'imageEntriesSchema.json');

        $dataObject1 = json_decode_real($this->response->getBody(), true);
        $randToken = $dataObject1['data']['randomToken'];
        Assertion::greaterThan(strlen($randToken), 10, "Random token appears to be too short");

        $imageIds = [];
        // Save the two image Ids retrieved by the first request.
        foreach ($dataObject1['data']['imageEntries'] as $imageEntry) {
            $imageIds[] = $imageEntry['imageId'];
        }
        //Check that there were two.
        Assertion::eq(2, count($imageIds), "Was expecting 2 images but got ".count($imageIds));

        //This should get the next 2 images.
        $path2 = sprintf(
            "/v1/imageEntries?competitionIdFilter=%d&sort=rand&limit=3&randomToken=%s&offset=2",
            $this->currentCompetition->getCompetitionId(),
            $randToken
        );
        $this->response = $this->makeGetRequest($api, $path2);

        // Check the response is valid.
        checkResponseAgainstSchema($this->response, 'imageEntriesSchema.json');

        // Save the two Ids retrieved by the second request
        $dataObject2 = json_decode_real($this->response->getBody(), true);
        foreach ($dataObject2['data']['imageEntries'] as $imageEntry) {
            $imageIds[] = $imageEntry['imageId'];
        }
        // Check that we have 4 images in total.
        Assertion::eq(4, count($imageIds), "Was expecting 4 images but have ".count($imageIds). "  last: ".$this->lastURI);

        // Check that all of the images Ids were retrieved.
        $this->checkElementsPresent($this->imageIds, $imageIds);
    }

    /**
     * @When /^(\d+) votes are cast for image (\d+)$/
     */
    public function votesAreCastForImage($voteCount, $imageNumber)
    {
        for ($x=0; $x<$voteCount; $x++) {
            $imageId = $this->imageIds[$imageNumber];
            $this->api->postVote($imageId);
        }
    }

    /**
     * @Given /^the image scores should be:$/
     */
    public function theImageScoresShouldBe(TableNode $table)
    {
        $expectedScores = [];

        foreach ($table->getHash() as $row) {
            $imageNumber = $row['image'];
            $imageId = $this->imageIds[$imageNumber];
            $expectedScores[$imageId] = $row['expectedScore'];
        }

        $imageEntriesWithScore = $this->api->getImageEntriesWithScore($this->currentCompetition->getCompetitionId());
        $message = sprintf(
            "Number of images in competition %d does not match expected value %d != %d",
            $this->currentCompetition->getCompetitionId(),
            count($imageEntriesWithScore),
            count($expectedScores)
        );

        Assertion::eq(count($imageEntriesWithScore), count($expectedScores),  $message);

        foreach ($imageEntriesWithScore as $imageEntryWithScore) {
            $imageId = $imageEntryWithScore->getImageEntry()->getImageId();
            $expectedScore = $expectedScores[$imageId];
            $message = sprintf(
                "Image %s does not have expected score. Expected %d != actual %d",
                $imageId,
                $expectedScore,
                $imageEntryWithScore->getScore()
            );
            Assertion::eq($expectedScore, $imageEntryWithScore->getScore(), $message);
        }
    }

    /**
     * @Given /^the competition stats should be:$/
     */
    public function theCompetitionStatsShouldBe(TableNode $table)
    {
        $competitionStats = $this->api->getCompetitionStats($this->currentCompetition->getCompetitionId());
        $competitionStatsArray = $competitionStats->toArray();

        foreach ($table->getRowsHash() as $key => $expectedValue) {
            $message = sprintf(
                "Competition stat '%s' for competition %d does not have expected value. Expected %d != actual %d. Full stats are:\n %s\n",
                $key,
                $this->currentCompetition->getCompetitionId(),
                $expectedValue,
                $competitionStatsArray[$key],
                var_export($competitionStatsArray, true)
            );

            Assertion::eq(
                $competitionStatsArray[$key],
                $expectedValue,
                $message
            );
        }
    }
}


