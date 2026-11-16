<?php

declare(strict_types=1);

namespace Friendzy\Widget\CustomerData;

use Friendzy\Widget\Model\CartShare;
use Magento\Checkout\Model\Cart;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Private cart section. customer/section/load answers it for this
 * shopper only; it is not part of the cached page.
 *
 * The fields are the ones a shared cart may carry. The quote id, the customer
 * and the form key are not among them.
 */
class CartSection implements SectionSourceInterface
{
    private const MAX_LINES = 20;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CartShare
     */
    private $share;

    /**
     * The minicart's cart already holds this shopper's quote. Reading the
     * checkout session instead can open a second, empty quote, and then a
     * signed-in shopper's lines never appear.
     *
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param CartShare $share
     */
    public function __construct(Cart $cart, StoreManagerInterface $storeManager, CartShare $share)
    {
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->share = $share;
    }

    /**
     * This shopper's cart, or an empty cart when the quote cannot be read.
     *
     * @return array<string, mixed>
     */
    public function getSectionData()
    {
        try {
            return $this->data();
        } catch (\Exception $e) {
            return ['currency' => '', 'count' => 0, 'subtotal' => 0, 'items' => []];
        }
    }

    /**
     * Currency, count, subtotal and the lines a friend may see.
     *
     * @return array<string, mixed>
     */
    private function data(): array
    {
        $quote = $this->cart->getQuote();
        $items = [];
        $count = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            try {
                $line = $this->share->line($this->fields($item));
            } catch (\Exception $e) {
                continue;
            }
            if ($line === []) {
                continue;
            }
            $count += (int) $line['quantity'];
            if (count($items) < self::MAX_LINES) {
                $items[] = $line;
            }
        }

        $subtotal = $this->subtotal($quote);
        if ($subtotal <= 0 && $items !== []) {
            $subtotal = 0.0;
            foreach ($items as $line) {
                $subtotal += (float) $line['rowTotal'];
            }
        }

        return [
            'currency' => $this->currency(),
            'count' => $count,
            'subtotal' => $subtotal,
            'items' => $items,
        ];
    }

    /**
     * The quote fields one shared line is built from.
     *
     * @param object $item
     * @return array<string, mixed>
     */
    private function fields($item): array
    {
        $product = $item->getProduct();
        $selected = [];
        $option = $item->getOptionByCode('attributes');
        if ($option && method_exists($option, 'getValue')) {
            $decoded = json_decode((string) $option->getValue(), true);
            if (is_array($decoded)) {
                $selected = $decoded;
            }
        }

        $url = '';
        if ($product && method_exists($product, 'getProductUrl')) {
            $url = (string) $product->getProductUrl();
        }

        // The quote item itself holds the id, the name and the sku. The product
        // object is only needed for the address, and it is not always loaded.
        $price = method_exists($item, 'getCalculationPrice') ? $item->getCalculationPrice() : 0;

        return [
            'key' => $item->getId(),
            'productId' => $item->getProductId() ?: ($product ? $product->getId() : ''),
            'sku' => $item->getSku() ?: ($product ? $product->getSku() : ''),
            'name' => $item->getName() ?: ($product ? $product->getName() : ''),
            'qty' => $item->getQty(),
            'price' => $price,
            'rowTotal' => $this->rowTotal($item, $price),
            'url' => $url,
            'selected' => $selected,
            'shown' => [],
        ];
    }

    /**
     * The row total once Magento has collected it.
     *
     * Until then, the unit price times the quantity, which is the same number for a line with no discount.
     *
     * @param object $item
     * @param mixed $price
     * @return float
     */
    private function rowTotal($item, $price): float
    {
        $row = method_exists($item, 'getRowTotal') ? $item->getRowTotal() : 0;
        if (is_numeric($row) && (float) $row > 0) {
            return (float) $row;
        }
        $qty = method_exists($item, 'getQty') ? (float) $item->getQty() : 0;

        return (is_numeric($price) ? (float) $price : 0.0) * ($qty > 0 ? $qty : 0);
    }

    /**
     * The store view's currency code, or an empty string when it is not three letters.
     *
     * @return string
     */
    private function currency(): string
    {
        $store = $this->storeManager->getStore();
        if (!method_exists($store, 'getCurrentCurrencyCode')) {
            return '';
        }
        $code = $store->getCurrentCurrencyCode();

        return is_string($code) && preg_match('/^[A-Za-z]{3}$/', $code) === 1 ? strtoupper($code) : '';
    }

    /**
     * The quote's subtotal, or zero when the totals are not collected yet.
     *
     * @param object $quote
     * @return float
     */
    private function subtotal($quote): float
    {
        try {
            $totals = $quote->getTotals();
            if (is_array($totals) && isset($totals['subtotal']) && is_object($totals['subtotal'])) {
                return (float) $totals['subtotal']->getValue();
            }
        } catch (\Exception $e) {
            return 0.0;
        }

        return 0.0;
    }
}
