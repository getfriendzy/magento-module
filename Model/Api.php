<?php

declare(strict_types=1);

namespace Friendzy\Widget\Model;

use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Friendzy's merchant API, for Connect: the merchant's own sign-in, then their site.
 * The token lives only for the length of one Connect; nothing here is stored.
 */
class Api
{
    private const TIMEOUT = 15;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SiteId
     */
    private $siteId;

    /**
     * @param CurlFactory $curlFactory
     * @param Json $json
     * @param Config $config
     * @param SiteId $siteId
     */
    public function __construct(CurlFactory $curlFactory, Json $json, Config $config, SiteId $siteId)
    {
        $this->curlFactory = $curlFactory;
        $this->json = $json;
        $this->config = $config;
        $this->siteId = $siteId;
    }

    /**
     * Sign in: a token, a two-factor challenge, or an error.
     *
     * @param string $email
     * @param string $password
     * @return array{token?: string, challenge?: string, error?: string}
     */
    public function signIn(string $email, string $password): array
    {
        [$status, $body] = $this->call('/merchant/login', ['email' => $email, 'password' => $password]);
        if ($status === 200 && !empty($body['two_factor']) && is_string($body['challenge'] ?? null)) {
            return ['challenge' => $body['challenge']];
        }
        if ($status === 200 && is_string($body['token'] ?? null)) {
            return ['token' => $body['token']];
        }

        return ['error' => $this->message($status, $body)];
    }

    /**
     * Answer the two-factor challenge with a six-digit code or a recovery code.
     *
     * @param string $challenge
     * @param string $code
     * @return array{token?: string, error?: string}
     */
    public function secondStep(string $challenge, string $code): array
    {
        $code = (string) preg_replace('/\s+/', '', $code);
        $field = preg_match('/^\d{6}$/', $code) ? 'code' : 'recovery_code';
        [$status, $body] = $this->call('/merchant/two-factor-challenge', ['challenge' => $challenge, $field => $code]);
        if ($status === 200 && is_string($body['token'] ?? null)) {
            return ['token' => $body['token']];
        }

        return ['error' => $this->message($status, $body)];
    }

    /**
     * The account's site for this address, registering it when there is none.
     *
     * @param string $token
     * @param string $name
     * @param string $website
     * @return array{site?: string, error?: string}
     */
    public function siteFor(string $token, string $name, string $website): array
    {
        $own = $this->ownSiteWith($token, $website);
        if ($own !== '') {
            return ['site' => $own];
        }
        $site = ['name' => $name, 'website' => $website, 'platform' => 'magento'];
        [$status, $body] = $this->call('/sites', $site, $token);
        // Saved, and joined onto an address: only an id that is one.
        $created = $status === 201 ? $this->siteId->clean($body['site'] ?? '') : '';
        if ($created !== '') {
            return ['site' => $created];
        }

        return ['error' => $this->message($status, $body)];
    }

    /**
     * Ask Friendzy to check the site now.
     *
     * @param string $token
     * @param string $site
     * @return void
     */
    public function verify(string $token, string $site): void
    {
        $this->call('/sites/' . $site . '/verify', [], $token);
    }

    /**
     * End the token.
     *
     * @param string $token
     * @return void
     */
    public function signOut(string $token): void
    {
        $this->call('/merchant/logout', [], $token);
    }

    /**
     * The account's own site on this address: a reconnect, or another store view on the same host.
     *
     * @param string $token
     * @param string $website
     * @return string
     */
    private function ownSiteWith(string $token, string $website): string
    {
        $host = $this->bareHost($website);
        [$status, $body] = $this->call('/merchant/me', null, $token);
        if ($status !== 200 || $host === '') {
            return '';
        }
        foreach ((array) ($body['shops'] ?? []) as $shop) {
            $site = $this->siteId->clean($shop['shop'] ?? '');
            if ($site === '') {
                continue;
            }
            [$status, $details] = $this->call('/sites/' . $site, null, $token);
            if ($status === 200 && $this->bareHost((string) ($details['website'] ?? '')) === $host) {
                return $site;
            }
        }

        return '';
    }

    /**
     * One call: a GET when there is no body, otherwise a POST. Redirects are not followed.
     *
     * @param string $path
     * @param array|null $body
     * @param string|null $token
     * @return array{0: int, 1: array}
     */
    private function call(string $path, ?array $body, ?string $token = null): array
    {
        $curl = $this->curlFactory->create();
        $curl->setTimeout(self::TIMEOUT);
        $curl->addHeader('Accept', 'application/json');
        if ($token !== null) {
            $curl->addHeader('Authorization', 'Bearer ' . $token);
        }
        $url = $this->config->appUrl() . '/api' . $path;
        try {
            if ($body === null) {
                $curl->get($url);
            } else {
                $curl->addHeader('Content-Type', 'application/json');
                $curl->post($url, $this->json->serialize((object) $body));
            }
            $decoded = $this->json->unserialize((string) $curl->getBody());
        } catch (\Exception $failed) {
            return [(int) $curl->getStatus(), []];
        }

        return [(int) $curl->getStatus(), is_array($decoded) ? $decoded : []];
    }

    /**
     * A website's host, without www.
     *
     * @param string $address
     * @return string
     */
    private function bareHost(string $address): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $host = parse_url(strpos($address, '://') === false ? 'https://' . $address : $address, PHP_URL_HOST);

        return is_string($host) ? (string) preg_replace('/^www\./', '', strtolower($host)) : '';
    }

    /**
     * What to tell the merchant.
     *
     * @param int $status
     * @param array $body
     * @return string
     */
    private function message(int $status, array $body): string
    {
        if ($status === 0) {
            return (string) __('Friendzy could not be reached. Try again in a minute.');
        }
        if ($status === 429) {
            return (string) __('Too many tries. Wait a few minutes, then try again.');
        }
        if (is_string($body['message'] ?? null) && $body['message'] !== '' && $status < 500) {
            return $body['message'];
        }

        return (string) __('Something went wrong on Friendzy\'s side. Try again in a minute.');
    }
}
