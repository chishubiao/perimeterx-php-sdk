<?php

namespace Perimeterx;

class PerimeterxCaptchaValidator extends PerimeterxRiskClient
{
    /**
     * @var string
     */
    private $pxCaptcha;

    /**
     * @param $pxCtx PerimeterxContext - perimeterx context
     * @param $pxConfig array - perimeterx configurations
     */
    public function __construct($pxCtx, $pxConfig)
    {
        parent::__construct($pxCtx, $pxConfig);
        $this->pxCaptcha = $pxCtx->getPxCaptcha();
    }

    private function sendCaptchaRequest($vid, $captcha)
    {
        $requestBody = [
            'request' => [
                'ip' => $this->pxCtx->getIp(),
                'headers' => $this->formatHeaders(),
                'uri' => $this->pxCtx->getUri()
            ],
            'pxCaptcha' => $captcha,
            'vid' => $vid,
            'hostname' => $this->pxCtx->getHostname()
        ];
        $headers = [
            'Authorization' => 'Bearer ' . $this->pxConfig['auth_token'],
            'Content-Type' => 'application/json'
        ];
        $response = $this->httpClient->send('/api/v1/risk/captcha', 'POST', $requestBody, $headers, $this->pxConfig['api_timeout'], $this->pxConfig['api_connect_timeout']);
        return $response;
    }

    public function verify()
    {
        try {
            if (!isset($this->pxCaptcha)) {
                return false;
            }
            /* remove pxCaptcha cookie to prevert reuse */
            setcookie("_pxCaptcha", "", time() - 3600);
            list($captcha, $vid) = explode(':', $this->pxCaptcha, 2);
            if (!isset($captcha)) {
                return false;
            }

            $this->pxCtx->setVid($vid);
            $response = json_decode($this->sendCaptchaRequest($vid, $captcha));
            if (isset($response->status) and $response->status == 0) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
