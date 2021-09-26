<?php

namespace App\Infrastructure\Browser\GoogleChrome;

use App\Application\Models\PowerPlant;
use App\Infrastructure\Browser\Interfaces\HuaweiPage;
use App\Infrastructure\Config\BrowserConfig;
use App\Infrastructure\Cryptography\PasswordEncrypter;
use DateTimeImmutable;
use HeadlessChromium\Page;

class ChromeHuaweiPage implements HuaweiPage
{
    public function __construct(
        private Page $libraryPage,
        private BrowserConfig $config,
        private PasswordEncrypter $encrypter,
        private PowerPlant $powerPlant
    ) {}

    public function login(): void
    {
        $username = $this->powerPlant->username();
        $password = $this->encrypter->decrypt($this->powerPlant->password());

        $usernameInputSelector = $this->config->selectors()->huawei()->loginInput();
        $passwordInputSelector = $this->config->selectors()->huawei()->passwordInput();
        $loginSubmitSelector = $this->config->selectors()->huawei()->loginSubmitButton();

        $this->libraryPage->evaluate("document.querySelector(\"$usernameInputSelector\").value=\"$username\"");
        $this->libraryPage->evaluate("document.querySelector(\"$passwordInputSelector\").value=\"$password\"");
        $this->libraryPage
            ->evaluate("document.querySelector(\"$loginSubmitSelector\").click()")
            ->waitForPageReload();
    }

    public function openDataSourceWithTimeSince(DateTimeImmutable $currentTime): array
    {
        $restResponseContentSelector = $this->config->selectors()->huawei()->restResponse();
        $this->libraryPage
            ->navigate(
                $this->config->links()->huawei()->dataPage($currentTime, $this->powerPlant->producerId())
            )
            ->waitForNavigation();
        $html = $this->libraryPage
            ->evaluate("document.querySelector(\"$restResponseContentSelector\").textContent")
            ->getReturnValue();
        return json_decode($html, true);
    }
}