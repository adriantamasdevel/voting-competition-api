<?php

use App\Exception\InvalidApiValueException;
use App\Exception\UnknownImageException;
use App\Exception\ContentNotFoundException;
use Gaufrette\Filesystem;
use Gaufrette\StreamWrapper;
use Gaufrette\Adapter\Local as LocalAdapter;
use Knp\Provider\ConsoleServiceProvider;

$autoloader = require(__DIR__.'/../vendor/autoload.php');

error_reporting(E_ALL);
setExceptionErrorHandler();

date_default_timezone_set('UTC');

$app = new Silex\Application();

define("ROOT_PATH", __DIR__ . "/..");

$domainName = 'localhost';

//set the environment
if( file_exists(ROOT_PATH . '/.env')) {
    $env_from_file = trim(file_get_contents(ROOT_PATH . '/.env'));
    $env = ($env_from_file == 'live') ? 'prod' : 'stage';
}else{
    $env = 'dev';
}

$app['env'] = $env;

try {
    $app->register(new Igorw\Silex\ConfigServiceProvider(ROOT_PATH."/resources/config/".$domainName."/prod.php"));

    if($env != 'prod') {
        $app->register(new Igorw\Silex\ConfigServiceProvider(ROOT_PATH."/resources/config/".$domainName."/".$env.".php"));
    }

} catch (\InvalidArgumentException $ex) {
    exit('The config file does not exist for this domain name');
}


//load services
$servicesLoader = new App\CliServicesLoader($app, true);
$servicesLoader->bindServicesIntoContainer();

$app['app.storage'] = $app['image.upload_path'];


$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'image-competition',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));


$console = $app['console'];
// DB stuff
$console->add(new \App\Command\DeleteTables('db:delete'));
$console->add(new \App\Command\MigrateTables('db:migrate'));
$console->add(new \App\Command\SanityCheck('db:sanitycheck'));


// Data stuff
$console->add(new \App\Command\CreateCompetition('data:createcompetition'));
$console->add(new \App\Command\ImportCompetition('data:importcompetition'));
$console->add(new \App\Command\RandomiseVoteIpAddress('data:ip_randomise'));
$console->add(new \App\Command\UpdateCompetitionStatus('data:updatecompstatus'));

// Cron stuff
$console->add(new \App\Command\CronImagesToModerate('cron:imagesToModerate'));
$console->add(new \App\Command\CronMonitorCompetitionStatus('cron:monitorCompetitionStatus'));

$console->run();