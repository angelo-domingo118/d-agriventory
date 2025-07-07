<?php

namespace App\Helpers;

use Illuminate\Support\HtmlString;

class TextHelper
{
    /**
     * Highlights search terms in a string of text.
     *
     * @param  string|null  $text  The text to highlight.
     * @param  string|array|null  $terms  The search term or terms to highlight.
     * @return string|HtmlString The highlighted text.
     */
    public static function highlight(?string $text, string|array|null $terms): string|HtmlString
    {
        if (empty($terms) || empty($text)) {
            return $text ?? '';
        }

        if (! is_array($terms)) {
            $terms = [$terms];
        }

        $terms = array_filter(array_unique($terms));

        if (empty($terms)) {
            return $text;
        }

        // Sort terms by length, descending, to avoid partial matches
        usort($terms, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $highlight = $text;
        foreach ($terms as $term) {
            $highlight = str_ireplace(
                $term,
                new HtmlString('<mark class="rounded-sm bg-yellow-200 px-0.5 dark:bg-yellow-700/50">'.htmlspecialchars($term, ENT_QUOTES, 'UTF-8', false).'</mark>'),
                $highlight
            );
        }

        return new HtmlString($highlight);
    }
}
