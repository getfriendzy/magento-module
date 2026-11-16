<?php

declare(strict_types=1);

namespace Friendzy\Widget\Model;

/**
 * A Friendzy site id, as pasted: the id itself, or the snippet's whole address.
 */
class SiteId
{
    private const PATTERN = '/^site-[a-z2-7]{12}$/';
    private const IN_SNIPPET_ADDRESS = '#/snippet/(site-[a-z2-7]{12})\.js#';

    /**
     * The id in the text, or an empty string when there is none.
     *
     * @param mixed $value
     * @return string
     */
    public function clean($value): string
    {
        if (!is_string($value)) {
            return '';
        }
        $value = strtolower(trim($value));
        if (preg_match(self::IN_SNIPPET_ADDRESS, $value, $match)) {
            $value = $match[1];
        }

        return preg_match(self::PATTERN, $value) ? $value : '';
    }
}
