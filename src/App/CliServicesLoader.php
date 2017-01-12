<?php

namespace App;

use App\Auth\Auth;
use App\Auth\AdminAuth;
use App\Repo\ImageEntryRepo;
use App\Repo\ImageEntryWithScoreRepo;
use App\Repo\CompetitionRepo;
use App\Repo\VoteRepo;
use App\Repo\Mock\ImageEntryMockRepo;
use App\Repo\Mock\CompetitionMockRepo;
use App\Repo\Mock\VoteMockRepo;
use App\Repo\Mock\ImageEntryWithScoreMockRepo;
use App\Repo\SQL\ImageEntrySqlRepo;
use App\Repo\SQL\CompetitionSqlRepo;
use App\Repo\SQL\ImageEntryWithScoreSqlRepo;
use App\Repo\SQL\VoteSqlRepo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Silex\Application;
use Silex\Route;


class CliServicesLoader
{
    protected $app;

    private $useRealDB;

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
            // Cli is allowed anything
            return new AdminAuth();
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
                /** @var $connection \Doctrine\DBAL\Connection */
                $connection = $this->app[Connection::class];
                /** @var $pdoCconnection \PDO */
                $pdoCconnection = $this->app[\PDO::class];

                return new ImageEntrySqlRepo($connection, $pdoCconnection);
            }

            return new ImageEntryMockRepo();
        });


        $this->app[ImageEntryWithScoreRepo::class] = $this->app->share( function() {

            if ($this->useRealDB === true) {
                /** @var $connection \Doctrine\DBAL\Connection */
                $connection = $this->app[Connection::class];
                /** @var $pdoCconnection \PDO */
                $pdoCconnection = $this->app[\PDO::class];

                return new ImageEntryWithScoreSqlRepo($connection, $pdoCconnection);
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

    }
}

