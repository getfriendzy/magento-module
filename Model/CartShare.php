<?php

declare(strict_types=1);

namespace Friendzy\Widget\Model;

/**
 * The cart a shopper may show a friend.
 *
 * Built from quote fields the section already has. The result is product,
 * options, quantity and price. It has no name, email, address, quote id or
 * form key. platforms/CART_ADAPTERS.md lists the fields.
 */
class CartShare
{
    /**
     * One line, or an empty array when the line cannot be added again.
     *
     * @param array $fields
     * @return array
     */
    public function line(array $fields): array
    {
        $productId = $this->digits($fields['productId'] ?? '');
        $title = $this->text($fields['name'] ?? '', 255);
        $quantity = $this->qty($fields['qty'] ?? 0);
        if ($productId === '' || $title === '' || $quantity < 1) {
            return [];
        }

        return [
            'key' => $this->text($fields['key'] ?? '', 64) !== '' ? $this->text($fields['key'] ?? '', 64) : $productId,
            'productId' => $productId,
            'sku' => $this->text($fields['sku'] ?? '', 64),
            'productTitle' => $title,
            'quantity' => $quantity,
            'unitPrice' => $this->money($fields['price'] ?? 0),
            'rowTotal' => $this->money($fields['rowTotal'] ?? 0),
            'url' => $this->url((string) ($fields['url'] ?? '')),
            'options' => $this->options(
                is_array($fields['selected'] ?? null) ? $fields['selected'] : [],
                is_array($fields['shown'] ?? null) ? $fields['shown'] : []
            ),
        ];
    }

    /**
     * Configurable options worth sending: the attribute and the chosen value,
     * which is what Magento's add form calls super_attribute. Labels are
     * catalogue text. Anything that is not a pair of ids is dropped.
     *
     * @param array<mixed> $selected Attribute id => option id, as the quote stores it.
     * @param array<mixed> $shown    Magento's option rows, for the label and the value text.
     * @return list<array{attributeId: string, optionId: string, label: string, value: string}>
     */
    public function options(array $selected, array $shown): array
    {
        $labels = [];
        foreach ($shown as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $this->digits($row['option_id'] ?? '');
            if ($id === '') {
                continue;
            }
            $labels[$id] = [
                'label' => $this->text($row['label'] ?? '', 80),
                'value' => $this->text($row['value'] ?? '', 80),
            ];
        }

        $out = [];
        foreach ($selected as $attributeId => $optionId) {
            if (count($out) >= 10) {
                break;
            }
            $attribute = $this->digits($attributeId);
            $option = $this->digits($optionId);
            if ($attribute === '' || $option === '') {
                continue;
            }
            $out[] = [
                'attributeId' => $attribute,
                'optionId' => $option,
                'label' => $labels[$attribute]['label'] ?? '',
                'value' => $labels[$attribute]['value'] ?? '',
            ];
        }

        return $out;
    }

    /**
     * A product address with the session and the form key taken off.
     *
     * @param string $url
     * @return string
     */
    public function url(string $url): string
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $path = parse_url($url, PHP_URL_PATH);

            return is_string($path) ? $path : '';
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || !isset($parts['scheme'])) {
            return '';
        }
        if ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https') {
            return '';
        }

        $query = [];
        if (!empty($parts['query'])) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            parse_str((string) $parts['query'], $query);
            unset($query['___SID'], $query['SID'], $query['form_key'], $query['uenc']);
        }

        $built = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $built .= ':' . $parts['port'];
        }
        $built .= $parts['path'] ?? '/';
        if ($query !== []) {
            $built .= '?' . http_build_query($query);
        }

        return $built;
    }

    /**
     * Digits only, at most twelve. Anything else is empty.
     *
     * @param mixed $value
     * @return string
     */
    private function digits($value): string
    {
        $text = is_scalar($value) ? (string) $value : '';

        return preg_match('/^[0-9]{1,12}$/', $text) === 1 ? $text : '';
    }

    /**
     * Trimmed text, cut to $max characters.
     *
     * @param mixed $value
     * @param int $max
     * @return string
     */
    private function text($value, int $max): string
    {
        $text = trim(is_scalar($value) ? (string) $value : '');
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $max);
        }

        return substr($text, 0, $max);
    }

    /**
     * A quantity from 1 to 99. Above 99 becomes 99. Anything else is zero.
     *
     * @param mixed $value
     * @return int
     */
    private function qty($value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }
        $number = (float) $value;
        if ($number < 1 || $number > 99) {
            return $number > 99 ? 99 : 0;
        }

        return (int) round($number);
    }

    /**
     * A positive amount, or zero.
     *
     * @param mixed $value
     * @return float
     */
    private function money($value): float
    {
        if (!is_numeric($value)) {
            return 0.0;
        }
        $number = (float) $value;

        return $number > 0 ? $number : 0.0;
    }
}
