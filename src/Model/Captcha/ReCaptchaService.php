<?php

namespace App\Model\Captcha;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod\CurlPost;

class ReCaptchaService
{
    /**
     * @var ReCaptcha
     */
    private ReCaptcha $recaptcha;
    public function __construct(private readonly string $secret)
    {
        $requestMethod = new CurlPost();
        $this->recaptcha = new ReCaptcha($this->secret, $requestMethod);
    }

    /**
     * @param string $gRecaptchaResponse
     * @return bool
     */
    public function isSuccessVerify(string $gRecaptchaResponse): bool
    {
        $response = $this->recaptcha->verify($gRecaptchaResponse, $_SERVER['REMOTE_ADDR']);
        return $response->isSuccess();
    }
}