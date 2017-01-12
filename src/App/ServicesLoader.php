<?php

namespace App;

use App\ApiParams;
use App\ApiParamsFactory;
use App\Model\ResponseFactory;
use App\Order\CompetitionOrder;


use App\Auth\Auth;
use App\Auth\AdminAuth;
use App\Auth\AnonAuth;

use App\Repo\ImageEntryRepo;
use App\Repo\ImageEntryWithScoreRepo;
use App\Repo\CompetitionRepo;
use App\Repo\VoteRepo;

use App\Repo\Mock\ImageEntryMockRepo;
use App\Repo\Mock\CompetitionMockRepo;
use App\Repo\Mock\VoteMockRepo;
use App\Repo\Mock\ImageEntryWithScoreMockRepo;

use App\Repo\SQL\CompetitionSqlRepo;
use App\Repo\SQL\ImageEntrySqlRepo;
use App\Repo\SQL\ImageEntryWithScoreSqlRepo;
use App\Repo\SQL\VoteSqlRepo;

use Silex\Application;
use App\Model\RandomOrderTokenFactory;
use App\Order\ImageEntryOrderFactory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Silex\Route;



class ServicesLoader
{
    protected $app;

    public function __construct(Application $app, $useRealDB)
    {
        $this->app = $app;
        $this->useRealDB = $useRealDB;
    }

    public function bindServicesIntoContainer()
    {

        $this->app[\PDO::class] = $this->app->share(function() {
            $connectionParams = $this->app['db.settings'];
            $dsn = sprintf(
                'mysql:dbname=%s;host=%s',
                $connectionParams["dbname"],
                $connectionParams["host"]
            );

            $options = [
                \PDO:: ATTR_EMULATE_PREPARES => false
            ];

            $pdo = new \PDO($dsn, $connectionParams["user"], $connectionParams["password"], $options);
            return $pdo;
        });

        $this->app[Connection::class] = $this->app->share(function() {
            $connectionParams['pdo'] = $this->app[\PDO::class];
            $conn = DriverManager::getConnection($connectionParams);

            return $conn;
        });

        $this->app['curl.provider'] = $this->app->share(function() {
            return new \Anchovy\CURLBundle\CURL\Curl();
        });

        $this->app['captcha.service'] = $this->app->share(function () {
            return new Services\GoogleRecaptchaService(
                $this->app['curl.provider'],
                $this->app['recaptcha.verifyUrl'],
                $this->app['recaptcha.secretKey']
            );
        });

        $this->app['slack.service'] = $this->app->share(function () {
            return new Services\SlackService(
                $this->app['curl.provider'],
                $this->app['slack.notificationWebhook']
            );
        });

        $this->app[Auth::class] = $this->app->share(function () {
            /** @var $request \Symfony\Component\HttpFoundation\Request */
            $request = $this->app['request_stack']->getCurrentRequest();
            if (stripos($request->getHost(), 'admin') !== false) {
                return new AdminAuth();
            }
            else {
                return new AnonAuth();
            }
        });

        $this->app[CompetitionRepo::class] = $this->app->share( function() {
            if ($this->useRealDB === true) {
                /** @var $connection \Doctrine\DBAL\Connection */
                $connection = $this->app[Connection::class];
                return new CompetitionSqlRepo($connection);
            }

            return new CompetitionMockRepo();
        });

        $this->app[ImageEntryRepo::class] = $this->app->share( function() {
            if ($this->useRealDB === true) {
                $pdo = $this->app[\PDO::class];
                /** @var $connection \Doctrine\DBAL\Connection */
                $connection = $this->app[Connection::class];
                return new ImageEntrySqlRepo($connection, $pdo);
            }

            return new ImageEntryMockRepo();
        });


        $this->app[ImageEntryWithScoreRepo::class] = $this->app->share( function() {
            if ($this->useRealDB === true) {
                /** @var $connection \Doctrine\DBAL\Connection */
                $connection = $this->app[Connection::class];
                $pdo = $this->app[\PDO::class];

                return new ImageEntryWithScoreSqlRepo($connection, $pdo);
            }

            return new ImageEntryWithScoreMockRepo();
        });

        $this->app[VoteRepo::class] = $this->app->share( function() {
            if ($this->useRealDB === true) {
                /** @var $connection \Doctrine\DBAL\Connection */
                $connection = $this->app[Connection::class];
                return new VoteSqlRepo($connection);
            }

            return new VoteMockRepo();
        });

        $this->app[ResponseFactory::class] = $this->app->share( function() {
            /** @var $auth \App\Auth\Auth */
            $auth = $this->app[Auth::class];
            $imageBaseUrl = $this->app['image.base_url'];

            if ($imageBaseUrl === null) {
                /** @var $request \Symfony\Component\HttpFoundation\Request */
                $request = $this->app['request_stack']->getCurrentRequest();
                $imageBaseUrl = $request->getSchemeAndHttpHost().'/'.$this->app['api.version'].'/images/';
            }

            return new ResponseFactory($auth, $imageBaseUrl, $this->app['image.default_width']);
        });

        $this->app[RandomOrderTokenFactory::class] = $this->app->share( function() {
            $imageEntryRepo = $this->app[ImageEntryRepo::class];

            return new RandomOrderTokenFactory($imageEntryRepo);
        });

//        $this->app[\App\Model\RandomOrderTokenFactory::class] = $this->app->share( function() {
//            $imageEntryRepo = $this->app[ImageEntryRepo::class];
//
//            return new RandomOrderTokenFactory($imageEntryRepo);
//        });


//        $this->app[\App\Model\RandomOrderTokenFactory::class] = $this->app->share( function() {
//            $imageEntryRepo = $this->app[ImageEntryRepo::class];
//
//            return new RandomOrderTokenFactory($imageEntryRepo);
//        });

        $this->app[\App\ApiParams::class] = $this->app->share( function() {
            $request = $this->app['request_stack']->getCurrentRequest();
            /** @var $apiParamsFactory \App\ApiParamsFactory */
            $apiParamsFactory = new ApiParamsFactory($request);
            return  $apiParamsFactory->createFromRouteParams([]);
        });

        $this->app[\App\Order\ImageEntryOrderFactory::class] = $this->app->share( function() {
            $imageEntryRepo = $this->app[ImageEntryRepo::class];
            $randomOrderTokenFactory = $this->app[RandomOrderTokenFactory::class];

            return new ImageEntryOrderFactory($randomOrderTokenFactory, $imageEntryRepo);
        });

        $this->app[\App\Order\ImageEntryWithScoreOrderFactory::class] = $this->app->share( function() {
            $imageEntryRepo = $this->app[ImageEntryRepo::class];
            $randomOrderTokenFactory = $this->app[RandomOrderTokenFactory::class];

            return new \App\Order\ImageEntryWithScoreOrderFactory($randomOrderTokenFactory, $imageEntryRepo);
        });
    }
}

