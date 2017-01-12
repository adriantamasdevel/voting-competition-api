<?php


use Silex\Application;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Gaufrette\Filesystem;
use Gaufrette\StreamWrapper;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Adapter\InMemory as InMemoryAdapter;
use Carbon\Carbon;
use App\TLDExtract;

use App\Exception\InvalidApiValueException;
use App\Exception\UnknownImageException;
use App\Exception\ContentNotFoundException;

use MJanssen\Provider\RoutingServiceProvider;

error_reporting(E_ALL);
setExceptionErrorHandler();
date_default_timezone_set('UTC');

define("ROOT_PATH", __DIR__ . "/..");

//$env = getenv('APP_ENV') ?: 'prod';
//set the environment
if( file_exists(ROOT_PATH . '/.env')) {
    $env_from_file = trim(file_get_contents(ROOT_PATH . '/.env'));
    $env = ($env_from_file == 'live') ? 'prod' : 'stage';
}else{
    $env = 'dev';
}

$app['env'] = $env;

//handling CORS preflight request
$app->before(function (Request $request) {
   if ($request->getMethod() === "OPTIONS") {
       $response = new Response();
       $response->headers->set("Access-Control-Allow-Origin","*");
       $response->headers->set("Access-Control-Allow-Methods","GET,POST,PATCH,PUT,DELETE,OPTIONS");
       $response->headers->set("Access-Control-Allow-Headers","Origin,Content-Type");
       $response->setStatusCode(200);
       return $response->send();
   }
}, Application::EARLY_EVENT);

//handling CORS respons with right headers
$app->after(function (Request $request, Response $response) {
    // @TODO - Don't set on prod server....
     $response->headers->set("Access-Control-Allow-Origin","*");
//    if ($response->headers->has("Access-Control-Allow-Origin") == false) {
//        $response->headers->set("Access-Control-Allow-Origin","*");
//    }
   $response->headers->set("Access-Control-Allow-Methods","GET,POST,PATCH,PUT,DELETE,OPTIONS");
});

//accepting JSON
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});


// extraction of the domain from hostname
$request = Request::createFromGlobals();

$tldExtract = new TLDExtract(true, ROOT_PATH."/resources/config/.tld_set");
$tldObject =  $tldExtract->extract($request->getHost());
$domainName = $tldObject->domain;

try {
    $app->register(new Igorw\Silex\ConfigServiceProvider(ROOT_PATH."/resources/config/".$domainName."/prod.php"));

    if($env != 'prod') {
        $app->register(new Igorw\Silex\ConfigServiceProvider(ROOT_PATH."/resources/config/".$domainName."/".$env.".php"));
    }

} catch (\InvalidArgumentException $ex) {
    exit('The config file does not exist for this domain name');
}

$app->register(new KPhoen\Provider\NegotiationServiceProvider());

//load utils
$utilsApi = new App\UtilsApi($app, $request);
//detect locale and pagination
$utilsApi->detectLocale();
$utilsApi->detectPagination();


//load services
$servicesLoader = new App\ServicesLoader($app, true);
$servicesLoader->bindServicesIntoContainer();




$app->register(new ServiceControllerServiceProvider());

$app->register(new HttpCacheServiceProvider(), array("http_cache.cache_dir" => ROOT_PATH . "/storage/cache/" . $app['user.locale']['iso'] . '/', 'http_cache.esi' => null));

$app['app.storage'] = $app['image.upload_path'];


$app->register(new MonologServiceProvider(), array(
    "monolog.logfile" => ROOT_PATH . "/storage/logs/" . Carbon::now('Europe/London')->format("Y-m-d") . ".log",
    "monolog.level" => $app["log.level"],
    "monolog.name" => "application"
));


$app->register(new Basster\Silex\Provider\Swagger\SwaggerProvider(), [
    "swagger.servicePath" => __DIR__ . '/App',
    "swagger.apiDocPath" => $app['api.version'] . '/api-docs',
]);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/App/Templates',
));

$app->register(new Neutron\Silex\Provider\ImagineServiceProvider(), array('imagine.driver' => 'Gd'));

//load routes
$routingServiceProvider = new RoutingServiceProvider();
$customRoutes = require ('routes.php');
$routingServiceProvider->addRoutes($app, $customRoutes);

$app->error(function (\Exception $e, $code) use ($app) {
    /** @var $request \Symfony\Component\HttpFoundation\Request */
    $request = $app['request_stack']->getCurrentRequest();

    if ($request) {
        // Log the request explicitly at the error level, as the normal route logging is only
        // at the info level, and so wouldn't be displayed for all log levels
        $app['monolog']->addError("Request to " . $request->getMethod() . " " . $request->getUri());
    }
    else {
        $app['monolog']->addError("Unknown request ");
    }

    $app['monolog']->addError($e->getMessage());
    $app['monolog']->addError($e->getTraceAsString());

    $errorResponse = createErrorResponseFromException($e);
    if ($errorResponse !== null) {
        return $errorResponse;
    }

    if ($app['app.showExceptions']) {
        return new JsonResponse(array("statusCode" => $code, "message" => $e->getMessage(), "stacktrace" => $e->getTraceAsString()));
    }

    return new JsonResponse(array("statusCode" => $code, "message" => "Apologies, an error occured."));
});

return $app;
