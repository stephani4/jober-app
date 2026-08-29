<?php

namespace App\Moderation;

use App\Models\Order;

/**
 * Отклоняет заказ, если в текстах есть нецензурная лексика.
 */
class ProfanityModerationRule implements OrderModerationRule
{
    public function evaluate(Order $order): ?string
    {
        $stems = config('moderation.profanity_stems', []);
        if (! is_array($stems) || $stems === []) {
            return null;
        }

        $haystack = $this->normalize($this->collectText($order));
        foreach ($stems as $stem) {
            if (! is_string($stem) || $stem === '') {
                continue;
            }
            if ($this->containsStem($haystack, $stem)) {
                return 'В заказе присутствует нецензурная лексика.';
            }
        }

        return null;
    }

    private function collectText(Order $order): string
    {
        $parts = [
            (string) $order->description,
        ];

        foreach ($order->points as $point) {
            $parts[] = (string) $point->description;
            $parts[] = (string) $point->address;
        }

        return implode("\n", $parts);
    }

    /**
     * Стем совпадает с началом слова, чтобы не ловить «потреблять» по «ебл».
     */
    private function containsStem(string $haystack, string $stem): bool
    {
        $normalized = $this->normalize($stem);
        if ($normalized === '') {
            return false;
        }

        $pattern = '/(?<![а-яa-z0-9])'.preg_quote($normalized, '/').'/u';

        return preg_match($pattern, $haystack) === 1;
    }

    private function normalize(string $value): string
    {
        $lower = mb_strtolower($value);

        return str_replace('ё', 'е', $lower);
    }
}
