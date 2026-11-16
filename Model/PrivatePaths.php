<?php

declare(strict_types=1);

namespace Friendzy\Widget\Model;

/**
 * Magento's private pages, as the widget reads them from the page: never shared with a friend.
 */
class PrivatePaths
{
    private const ROUTES = [
        'checkout',
        'customer',
        'sales',
        'wishlist',
        'multishipping',
        'vault',
        'downloadable/customer',
        'review/customer',
        'newsletter/manage',
        'paypal',
    ];

    /**
     * The routes under a store's own address, so a store view at /de/ names /de/checkout.
     *
     * @param string $storeBasePath The path of the store's base URL, such as "/" or "/de/".
     * @return string Comma separated, as <meta name="friendzy-private-paths"> takes them.
     */
    public function forBasePath(string $storeBasePath): string
    {
        $base = '/' . trim($storeBasePath, '/');
        $base = $base === '/' ? '' : $base;

        return implode(',', array_map(static function (string $route) use ($base): string {
            return $base . '/' . $route;
        }, self::ROUTES));
    }
}
