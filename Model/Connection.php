<?php

declare(strict_types=1);

namespace Friendzy\Widget\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Connect: signs in to the merchant's Friendzy account, finds or registers the site for a
 * store view, saves its id and signs out. Only the id is kept.
 */
class Connection
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var ReinitableConfigInterface
     */
    private $config;

    /**
     * @var TypeListInterface
     */
    private $cacheTypes;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Api $api
     * @param WriterInterface $writer
     * @param ReinitableConfigInterface $config
     * @param TypeListInterface $cacheTypes
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Api $api,
        WriterInterface $writer,
        ReinitableConfigInterface $config,
        TypeListInterface $cacheTypes,
        StoreManagerInterface $storeManager
    ) {
        $this->api = $api;
        $this->writer = $writer;
        $this->config = $config;
        $this->cacheTypes = $cacheTypes;
        $this->storeManager = $storeManager;
    }

    /**
     * The first step: connected, or a challenge to answer, or an error.
     *
     * @param int $storeId
     * @param string $email
     * @param string $password
     * @return array{site?: string, challenge?: string, error?: string}
     */
    public function connect(int $storeId, string $email, string $password): array
    {
        $signedIn = $this->api->signIn($email, $password);

        return isset($signedIn['token']) ? $this->finish($storeId, $signedIn['token']) : $signedIn;
    }

    /**
     * The second step, with the code from the merchant's authenticator.
     *
     * @param int $storeId
     * @param string $challenge
     * @param string $code
     * @return array{site?: string, error?: string}
     */
    public function answer(int $storeId, string $challenge, string $code): array
    {
        $signedIn = $this->api->secondStep($challenge, $code);

        return isset($signedIn['token']) ? $this->finish($storeId, $signedIn['token']) : $signedIn;
    }

    /**
     * Take this store view off Friendzy. The account and its site stay.
     *
     * An empty value at the store view hides a site id inherited from a wider scope.
     * Deleting the row would only bring that inherited id back, and the wider value
     * stays so the other store views keep it.
     *
     * @param int $storeId
     * @return void
     */
    public function disconnect(int $storeId): void
    {
        $this->writer->save(Config::PATH_SITE_ID, '', ScopeInterface::SCOPE_STORES, $storeId);
        $this->refresh();
    }

    /**
     * Find the site, keep its id, and end the token whatever happened.
     *
     * @param int $storeId
     * @param string $token
     * @return array{site?: string, error?: string}
     */
    private function finish(int $storeId, string $token): array
    {
        $store = $this->storeManager->getStore($storeId);
        $website = (string) $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
        $found = $this->api->siteFor($token, (string) $store->getName(), $website);
        if (isset($found['site'])) {
            $this->writer->save(Config::PATH_SITE_ID, $found['site'], ScopeInterface::SCOPE_STORES, $storeId);
            $this->refresh();
            $this->api->verify($token, $found['site']);
        }
        $this->api->signOut($token);

        return $found;
    }

    /**
     * Pages already cached still carry the old head.
     *
     * @return void
     */
    private function refresh(): void
    {
        $this->config->reinit();
        $this->cacheTypes->cleanType('config');
        $this->cacheTypes->cleanType('full_page');
    }
}
