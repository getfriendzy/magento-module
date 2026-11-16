<?php

declare(strict_types=1);

namespace Friendzy\Widget\ViewModel;

use Friendzy\Widget\Model\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * The order just placed, for the confirmation page's order line. It reads the buyer's own
 * session, so it is only ever used on a page that is not cached.
 */
class OrderLine implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Config $config
     * @param Session $session
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Config $config, Session $session, StoreManagerInterface $storeManager)
    {
        $this->config = $config;
        $this->session = $session;
        $this->storeManager = $storeManager;
    }

    /**
     * The order's number, total and currency, or null when there is nothing to report.
     *
     * @return array{number: string, total: string, currency: string}|null
     */
    public function order(): ?array
    {
        if ($this->config->siteId($this->storeManager->getStore()->getId()) === '') {
            return null;
        }
        $order = $this->session->getLastRealOrder();
        $number = trim((string) $order->getIncrementId());
        $currency = strtoupper(trim((string) $order->getOrderCurrencyCode()));
        $total = $order->getGrandTotal();
        // A cancelled order is not a sale. A missing number, total or currency is not one either:
        // the order line is only written when all three are there.
        if ($number === ''
            || $total === null
            || $total === ''
            || $currency === ''
            || $order->getState() === Order::STATE_CANCELED
        ) {
            return null;
        }

        return [
            'number' => $number,
            'total' => number_format((float) $total, 2, '.', ''),
            'currency' => $currency,
        ];
    }
}
