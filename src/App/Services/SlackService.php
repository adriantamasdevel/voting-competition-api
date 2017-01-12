<?php

namespace App\Services;

class SlackService extends BaseService
{

    protected $curlProvider;
    protected $url;
    protected $secretKey;

    public function __construct($curlProvider, $url)
    {
        $this->curlProvider = $curlProvider;
        $this->url = $url;


    }

    public function postMessage($slackRequest)
    {
        try {
            return $this->curlProvider
                ->setURL($this->url)
                ->setMethod('POST', $slackRequest)
                ->setOption('CURLOPT_NOBODY', FALSE)
                ->setOption('CURLOPT_TIMEOUT', 5)
                ->setOption('CURLOPT_CONNECTTIMEOUT', 2)
                ->setOption('CURLOPT_FAILONERROR', true)
                ->setOption('CURLOPT_SSLVERSION', 0)
                ->execute();
        } catch (\InvalidArgumentException $ex) {
            return false;
        }
    }


    public function send($slackRequest)
    {
//        $slackRequest = array_merge(
//            $slackRequest,
//            array(
//                'payload' => $this->secretKey
//            )
//        );
        return $this->postMessage($slackRequest);

    }
}
