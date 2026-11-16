<?php

declare(strict_types=1);

namespace Friendzy\Widget\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * The module's settings. The site id is per store view, so a store view can be its own site.
 */
class Config
{
    public const PATH_SITE_ID = 'friendzy/general/site_id';
    private const PATH_APP_URL = 'friendzy/general/app_url';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SiteId
     */
    private $siteId;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SiteId $siteId
     */
    public function __construct(ScopeConfigInterface $scopeConfig, SiteId $siteId)
    {
        $this->scopeConfig = $scopeConfig;
        $this->siteId = $siteId;
    }

    /**
     * The store view's site id, or an empty string when it has none.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function siteId($storeId = null): string
    {
        return $this->siteId->clean(
            $this->scopeConfig->getValue(self::PATH_SITE_ID, ScopeInterface::SCOPE_STORE, $storeId)
        );
    }

    /**
     * Where Friendzy is, with no trailing slash.
     *
     * @return string
     */
    public function appUrl(): string
    {
        return rtrim((string) $this->scopeConfig->getValue(self::PATH_APP_URL), '/');
    }

    /**
     * Where "Open your Friendzy admin" goes.
     *
     * The link is this store's Friendzy base plus /merchant. A base of
     * friendzy.ro or getfriendzy.com, with or without www, stays on that
     * base. Laravel forwards /merchant to the app host when FRIENDZY_APP_HOST
     * is set, and serves the admin on this base when it is not.
     *
     * @return string
     */
    public function adminUrl(): string
    {
        return $this->appUrl() . '/merchant';
    }

    /**
     * The address of a site's snippet.
     *
     * @param string $site
     * @return string
     */
    public function snippetUrl(string $site): string
    {
        return $this->appUrl() . '/snippet/' . $site . '.js';
    }
}
