<?php
namespace App;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UtilsApi
{

    protected $app;

    public function __construct(Application $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
        $this->defaultLocale = $app['default.locale'];
    }


    public function detectLocale()
    {

        $this->app['user.locale'] = $this->app->share( function($app) {
            $requestedLanguage =  $this->request->query->get('locale');
            $currencyLookupTable = parse_ini_file(ROOT_PATH . '/resources/config/locales.ini', true);

            if(isset($currencyLookupTable[strtoupper($requestedLanguage)]))
            {
                return $currencyLookupTable[strtoupper($requestedLanguage)];
            }

            return $currencyLookupTable[$this->defaultLocale];
        });
    }

    public function detectPagination()
    {

        $this->app['pagination'] = $this->app->share( function($app) {

            //get offset/limit
            $offset = $this->request->query->get('offset');
            $limit = $this->request->query->get('limit');
            $offset = null == $offset ? 0 : $offset;
            $limit = null == $limit ? 30 : $limit;


            return array('offset' => $offset, 'limit' => $limit);
        });
    }


}
