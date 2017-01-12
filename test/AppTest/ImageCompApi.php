<?php

namespace AppTest;

use Amp\Artax\Client as ArtaxClient;
use Amp\Artax\Response;
use App\Model\Entity\Competition;
use App\Model\Entity\ImageEntryWithScore;

use App\Model\CompetitionStats;

use Assert\Assertion;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;



class ImageCompApi
{
    /** @var  ArtaxClient */
    public $client;

    /** @var Response */
    private $response;

    private $apiEndpoints = array();

    private $lastURI;

    public function __construct()
    {
        $this->client = new ArtaxClient();

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
    }

    private function getURL($api, $path)
    {
        return $this->apiEndpoints[$api] . $path;
    }

    public function postVote($imageId)
    {
        $body = (new \Amp\Artax\FormBody);
        $body->addFields(["imageId" => $imageId]);
        $uri = $this->getURL('image.admin.api', '/v1/votes');
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod('POST')
            ->setUri($uri)
            ->setBody($body);

        $promise = $this->client->request($request);
        $this->response = \Amp\wait($promise);
        Assertion::eq(200, $this->response->getStatus());
    }

    /**
     * @param $competitionId
     * @return Competition
     * @throws \Exception
     * @throws \Throwable
     */
    public function getCompetition($competitionId)
    {
        $body = (new \Amp\Artax\FormBody);
        $body->addFields(["competitionId" => $competitionId]);
        $uri = $this->getURL('image.admin.api', '/v1/competitions');
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod('GET')
            ->setUri($uri)
            ->setBody($body);

        $promise = $this->client->request($request);
        $this->response = \Amp\wait($promise);
        Assertion::eq(200, $this->response->getStatus());

        $body = $this->response->getBody();
        $data = json_decode_real($body, false);

        return Competition::fromArray($data['data']['competition']);
    }

    /**
     * @param null $competitionIdFilter
     * @param null $statusFilter
     * @param null $imageWidth
     * @param null $randomToken
     * @param null $offset
     * @param null $limit
     * @return ImageEntryWithScore[]
     * @throws \Exception
     * @throws \Throwable
     */
    public function getImageEntriesWithScore(
        $competitionIdFilter = null,
        $statusFilter = null,
        $imageWidth = null,
        $randomToken = null,
        $offset = null,
        $limit = null
    ) {

        $body = (new \Amp\Artax\FormBody);
        $params = [
            'competitionIdFilter',
            'statusFilter',
            'imageWidth',
            'randomToken',
            'offset',
            'limit',
        ];

        $data = [];
        foreach($params as $param) {
            if ($$param !== null) {
                $data[$param] = $$param;
            }
        }

        $body->addFields($data);

        $uri = $this->getURL('image.admin.api', '/v1/imageEntriesWithScore');
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod('GET')
            ->setUri($uri)
            ->setBody($body);

        $promise = $this->client->request($request);
        $this->response = \Amp\wait($promise);
        Assertion::eq(200, $this->response->getStatus());

        $body = $this->response->getBody();
        $data = json_decode_real($body, true);

        $entries = $data['data']['imageEntriesWithScore'];

        $imageEntriesWithScore = [];

        foreach ($entries as $entry) {
            $imageEntriesWithScore[] = ImageEntryWithScore::fromArray($entry);
        }

        return $imageEntriesWithScore;
    }

    private function get($uri)
    {
        $this->lastURI = $uri;
        $request = (new \Amp\Artax\Request)
            ->setMethod('GET')
            ->setUri($uri);

        $promise = $this->client->request($request);
        $this->response = \Amp\wait($promise);
        Assertion::eq(200, $this->response->getStatus());

        $body = $this->response->getBody();
        $data = json_decode_real($body, true);

        return $data;
    }

    public function getCompetitionStats($competitionId)
    {
        $uri = $this->getURL('image.admin.api', "/v1/competitions/$competitionId/stats");
        $data = $this->get($uri);

        return CompetitionStats::fromArray($data['data']['competitionStats']);
    }
}
