<?php

namespace App\Model\Captcha;

use ReCaptcha\ReCaptcha;

class ReCaptchaService
{
    /**
     * @var ReCaptcha
     */
    private ReCaptcha $recaptcha;
    public function __construct(private readonly string $secret)
    {
        $this->recaptcha = new ReCaptcha($this->secret);
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