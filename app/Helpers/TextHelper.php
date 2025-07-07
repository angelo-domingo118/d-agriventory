<?php

namespace App\Helpers;

use Illuminate\Support\HtmlString;

class TextHelper
{
    /**
     * Highlights search terms in a string of text.
     *
     * @param string|null $text The text to highlight.
     * @param string|array|null $terms The search term or terms to highlight.
     * @return string|HtmlString The highlighted text.
     */
    public static function highlight(?string $text, string|array|null $terms): string|HtmlString
    {
        if (empty($terms) || empty($text)) {
            return $text ?? '';
        }

        if (!is_array($terms)) {
            $terms = [$terms];
        }

        $terms = array_filter($terms);

        if (empty($terms)) {
            return $text;
        }

        $escapedTerms = array_map(fn($term) => preg_quote($term, '/'), $terms);
        $pattern = '/(' . implode('|', $escapedTerms) . ')/i';

        return new HtmlString(
            preg_replace($pattern, '<mark class="rounded-sm bg-yellow-200 px-0.5 dark:bg-yellow-700/50">$1</mark>', $text)
        );
    }
} 