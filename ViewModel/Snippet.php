<?php

declare(strict_types=1);

namespace Friendzy\Widget\ViewModel;

use Friendzy\Widget\Model\Config;
use Friendzy\Widget\Model\PrivatePaths;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * What the page head needs. It depends only on the store view, so the page stays cacheable.
 */
class Snippet implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var PrivatePaths
     */
    private $privatePaths;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Config $config
     * @param PrivatePaths $privatePaths
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Config $config, PrivatePaths $privatePaths, StoreManagerInterface $storeManager)
    {
        $this->config = $config;
        $this->privatePaths = $privatePaths;
        $this->storeManager = $storeManager;
    }

    /**
     * The snippet's address, or an empty string when this store view has no site.
     *
     * @return string
     */
    public function url(): string
    {
        $site = $this->config->siteId($this->storeManager->getStore()->getId());

        return $site === '' ? '' : $this->config->snippetUrl($site);
    }

    /**
     * The store's private paths, comma separated.
     *
     * @return string
     */
    public function privatePaths(): string
    {
        return $this->privatePaths->forBasePath($this->basePath());
    }

    /**
     * The store view's path ("/" or "/de/").
     *
     * The same for every shopper, so the page cache may keep it. The form key is not here: that is a cookie.
     *
     * @return string
     */
    public function cartBase(): string
    {
        $path = $this->basePath();
        if ($path === '/' || $path === '') {
            return '/';
        }

        return str_ends_with($path, '/') ? $path : $path . '/';
    }

    /**
     * The store view's base path, from its link base URL.
     *
     * @return string
     */
    private function basePath(): string
    {
        $base = (string) $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK);
        $path = parse_url($base, PHP_URL_PATH); // phpcs:ignore Magento2.Functions.DiscouragedFunction

        return is_string($path) && $path !== '' ? $path : '/';
    }
}
