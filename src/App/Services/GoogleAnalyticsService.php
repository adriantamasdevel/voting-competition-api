<?php

namespace App\Services;

class GoogleAnalyticsService extends BaseService
{

    protected $curlProvider;
    protected $url;
    protected $trackingId;

    public function __construct($curlProvider, $gaUrl, $gaTrackingId)
    {
        $this->curlProvider = $curlProvider;
        $this->url = $gaUrl;
        $this->trackingId = $gaTrackingId;


    }

    public function postEvent($gaRequest)
    {
        try {
            return $this->curlProvider
                ->setURL($this->url)
                ->setMethod('POST', $gaRequest)
                ->setOption('CURLOPT_NOBODY', FALSE)
                ->setOption('CURLOPT_TIMEOUT', 5)
                ->setOption('CURLOPT_CONNECTTIMEOUT', 2)
                ->setOption('CURLOPT_FAILONERROR', true)
                ->setOption('CURLOPT_RETURNTRANSFER', false)
                ->execute();
        } catch (\InvalidArgumentException $ex) {
            return false;
        }
    }


    public function sentGoogleEvent($gaRequest)
    {


        $gaRequest = array_merge(
            $gaRequest,
            array(
                'v' => '1',
                'tid' => $this->trackingId,
                't' => 'event'
            )
        );


        return $this->postEvent($gaRequest);

    }
}
