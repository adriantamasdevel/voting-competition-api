<?php

namespace App\Services;

class GoogleRecaptchaService extends BaseService
{

    protected $curlProvider;
    protected $url;
    protected $secretKey;

    public function __construct($curlProvider, $captchaVerifyUrl, $captchaSecretKey)
    {
        $this->curlProvider = $curlProvider;
        $this->url = $captchaVerifyUrl;
        $this->secretKey = $captchaSecretKey;


    }

    public function postVerify($captchaRequest)
    {
        try {
            return $this->curlProvider
                ->setURL($this->url)
                ->setMethod('POST', $captchaRequest)
                ->setOption('CURLOPT_SSLVERSION', 0)
                ->setOption('CURLOPT_NOBODY', FALSE)
                ->setOption('CURLOPT_TIMEOUT', 5)
                ->setOption('CURLOPT_CONNECTTIMEOUT', 2)
                ->setOption('CURLOPT_FAILONERROR', true)
                ->execute();
        } catch (\InvalidArgumentException $ex) {
            return false;
        }
    }


    public function verify($captchaRequest)
    {
        $captchaRequest = array_merge(
            $captchaRequest,
            array(
                'secret' => $this->secretKey
            )
        );


        return $this->postVerify($captchaRequest);

    }
}
