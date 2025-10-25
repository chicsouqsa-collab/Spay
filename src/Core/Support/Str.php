<?php

/**
 * This class is responsible to provide functions to process on string.
 *
 * @package StellarPay\Core\Support
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Support;

use function sanitize_title;

/**
 * @since 1.0.0
 */
class Str
{
    /**
     * The cache of snake-cased words.
     *
     * @since 1.0.0
     */
    protected static array $snakeCache = [];

    /**
     * The cache of camel-cased words.
     *
     * @since 1.0.0
     */
    protected static array $camelCache = [];

    /**
     * The cache of studly cased words.
     *
     * @since 1.0.0
     */
    protected static array $studlyCache = [];

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     *
     * @since 1.0.0
     */
    public static function after(string $subject, string $search): string
    {
        return '' === $search ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * Return the remainder of a string after the last occurrence of a given value.
     *
     * @since 1.0.0
     */
    public static function afterLast(string $subject, string $search): string
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if (false === $position) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     *
     * @since 1.0.0
     */
    public static function before(string $subject, string $search): string
    {
        if ('' === $search) {
            return $subject;
        }

        $result = strstr($subject, $search, true);

        return false === $result ? $subject : $result;
    }

    /**
     * Get the portion of a string before the last occurrence of a given value.
     *
     * @since 1.0.0
     */
    public static function beforeLast(string $subject, string $search): string
    {
        if ('' === $search) {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if (false === $pos) {
            return $subject;
        }

        return static::substr($subject, 0, $pos);
    }

    /**
     * Get the portion of a string between two given values.
     *
     * @since 1.0.0
     */
    public static function between(string $subject, string $from, string $to): string
    {
        if ('' === $from || '' === $to) {
            return $subject;
        }

        return static::beforeLast(static::after($subject, $from), $to);
    }

    /**
     * Get the smallest possible portion of a string between two given values.
     *
     * @since 1.0.0
     */
    public static function betweenFirst(string $subject, string $from, string $to): string
    {
        if ('' === $from || '' === $to) {
            return $subject;
        }

        return static::before(static::after($subject, $from), $to);
    }

    /**
     * Convert a value to camel case.
     *
     * @since 1.0.0
     */
    public static function camel(string $value): string
    {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly(static::lower($value)));
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @since 1.0.0
     *
     * @param  string|string[]  $needles
     */
    public static function contains(string $haystack, $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
            $needles = array_map('mb_strtolower', (array) $needles);
        }

        foreach ((array) $needles as $needle) {
            if ('' !== $needle && false !== strpos($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string contains all array values.
     *
     * @since 1.0.0
     */
    public static function containsAll(string $haystack, array $needles, bool $ignoreCase = false): bool
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
            $needles = array_map('mb_strtolower', $needles);
        }

        foreach ($needles as $needle) {
            if (! static::contains($haystack, $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @since 1.0.0
     *
     * @param  string|string[]  $needles
     */
    public static function endsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if (
                '' !== $needle
                && substr($haystack, - strlen($needle)) === $needle
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @since 1.0.0
     */
    public static function finish(string $value, string $cap): string
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * Determine if a given string is a valid UUID.
     *
     * @since 1.0.0
     */
    public static function isUuid(string $value): bool
    {
        return preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $value) > 0;
    }

    /**
     * Convert a string to kebab case.
     *
     * @since 1.0.0
     */
    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    /**
     * Return the length of the given string.
     *
     * @since 1.0.0
     */
    public static function length(string $value, string $encoding = null): int
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }

        return mb_strlen($value);
    }

    /**
     * Limit the number of characters in a string.
     *
     * @since 1.0.0
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Convert the given string to lower-case.
     *
     * @since 1.0.0
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Limit the number of words in a string.
     *
     * @since 1.0.0
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);

        if (! isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]) . $end;
    }

    /**
     * Masks a portion of a string with a repeated character.
     *
     * @since 1.0.0
     */
    public static function mask(string $string, string $character, int $index, int $length = null, string $encoding = 'UTF-8'): string
    {
        if ('' === $character) {
            return $string;
        }

        if (is_null($length) && PHP_MAJOR_VERSION < 8) {
            $length = mb_strlen($string, $encoding);
        }

        $segment = mb_substr($string, $index, $length, $encoding);

        if ('' === $segment) {
            return $string;
        }

        $start = mb_substr($string, 0, mb_strpos($string, $segment, 0, $encoding), $encoding);
        $end = mb_substr($string, mb_strpos($string, $segment, 0, $encoding) + mb_strlen($segment, $encoding));

        return $start . str_repeat(mb_substr($character, 0, 1, $encoding), mb_strlen($segment, $encoding)) . $end;
    }

    /**
     * Get the string matching the given pattern.
     *
     * @since 1.0.0
     */
    public static function match(string $pattern, string $subject): string
    {
        preg_match($pattern, $subject, $matches);

        if (! $matches) {
            return '';
        }

        return $matches[1] ?? $matches[0];
    }

    /**
     * Pad both sides of a string with another.
     *
     * @since 1.0.0
     */
    public static function padBoth(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * Pad the left side of a string with another.
     *
     * @since 1.0.0
     */
    public static function padLeft(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * Pad the right side of a string with another.
     *
     * @since 1.0.0
     */
    public static function padRight(string $value, int $length, string $pad = ' '): string
    {
        return str_pad($value, $length, $pad, STR_PAD_RIGHT);
    }

    /**
     * Parse a Class[@]method style callback into class and method.
     *
     * @since 1.0.0
     */
    public static function parseCallback(string $callback, string $default = null): array
    {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @since 1.0.0
     */
    public static function random(int $length = 16): string
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * Repeat the given string.
     *
     * @since 1.0.0
     */
    public static function repeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @since 1.0.0
     */
    public static function replaceArray(string $search, array $replace, string $subject): string
    {
        $segments = explode($search, $subject);

        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $value = array_shift($replace);
            $result .= (null !== $value ? $value : $search) . $segment;
        }

        return $result;
    }

    /**
     * Replace the given value in the given string.
     *
     * @since 1.0.0
     *
     * @param  string|string[]  $search
     * @param  string|string[]  $replace
     * @param  string|string[]  $subject
     */
    public static function replace($search, $replace, $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @since 1.0.0
     */
    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strpos($subject, $search);

        if (false !== $position) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @since 1.0.0
     */
    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if (false !== $position) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Remove any occurrence of the given string in the subject.
     *
     * @since 1.0.0
     */
    public static function remove($search, string $subject, bool $caseSensitive = true): string
    {
        return $caseSensitive
            ? str_replace($search, '', $subject)
            : str_ireplace($search, '', $subject);
    }

    /**
     * Reverse the given string.
     *
     * @since 1.0.0
     */
    public static function reverse(string $value): string
    {
        // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.mb_str_splitFound
        return implode(array_reverse(mb_str_split($value)));
    }

    /**
     * Begin a string with a single instance of a given value.
     *
     * @since 1.0.0
     */
    public static function start(string $value, string $prefix): string
    {
        $quoted = preg_quote($prefix, '/');

        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
    }

    /**
     * Convert the given string to upper-case.
     *
     * @since 1.0.0
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert the given string to a title case.
     *
     * @since 1.0.0
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert the given string to a title case for each word.
     *
     * @since 1.0.0
     */
    public static function headline(string $value): string
    {
        $parts = explode(' ', $value);

        $parts = count($parts) > 1
            ? array_map([static::class, 'title'], $parts)
            : array_map([static::class, 'title'], static::ucsplit(implode('_', $parts)));

        $collapsed = static::replace(['-', '_', ' '], '_', implode('_', $parts));

        return implode(' ', array_filter(explode('_', $collapsed)));
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @since 1.0.0
     */
    public static function slug(string $title, string $fallbackTitle = '', string $context = 'save'): string
    {
        return sanitize_title($title, $fallbackTitle, $context);
    }

    /**
     * Convert a string to snake case.
     *
     * @since 1.0.0
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;

        if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @since 1.0.0
     *
     * @param  string|string[]  $needles
     */
    public static function startsWith(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ('' !== $needle && 0 === strncmp($haystack, $needle, strlen($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a value to stud caps case.
     *
     * @since 1.0.0
     */
    public static function studly(string $value): string
    {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $words = explode(' ', static::replace(['-', '_'], ' ', $value));

        $studlyWords = array_map(static function ($word) {
            return static::ucfirst($word);
        }, $words);

        return static::$studlyCache[$key] = implode($studlyWords);
    }

    /**
     * Returns the portion of the string specified by the start and length parameters.
     *
     * @since 1.0.0
     */
    public static function substr(string $string, int $start, int $length = null): string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Returns the number of substring occurrences.
     *
     * @since 1.0.0
     */
    public static function substrCount(string $haystack, string $needle, int $offset = 0, int $length = null): int
    {
        if (! is_null($length)) {
            return substr_count($haystack, $needle, $offset, $length);
        }

        return substr_count($haystack, $needle, $offset);
    }

    /**
     * Replace text within a portion of a string.
     *
     * @since 1.0.0
     *
     * @param  string|array  $string
     * @param  string|array  $replace
     * @param  array|int  $offset
     * @param  array|int|null  $length
     *
     * @return string|array
     */
    public static function substrReplace($string, $replace, $offset = 0, $length = null)
    {
        if (null === $length) {
            $length = strlen($string);
        }

        return substr_replace($string, $replace, $offset, $length);
    }

    /**
     * Swap multiple keywords in a string with other keywords.
     *
     * @since 1.0.0
     */
    public static function swap(array $map, string $subject): string
    {
        return strtr($subject, $map);
    }

    /**
     * Make a string's first character lowercase.
     *
     * @since 1.0.0
     */
    public static function lcfirst(string $string): string
    {
        return static::lower(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    /**
     * Make a string's first character uppercase.
     *
     * @since 1.0.0
     */
    public static function ucfirst(string $string): string
    {
        return static::upper(static::substr($string, 0, 1)) . static::substr($string, 1);
    }

    /**
     * Split a string into pieces by uppercase characters.
     *
     * @since 1.0.0
     */
    public static function ucsplit(string $string): array
    {
        return preg_split('/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Get the number of words a string contains.
     *
     * @since 1.0.0
     */
    public static function wordCount(string $string): int
    {
        return str_word_count($string);
    }

    /**
     * Remove all strings from the casing caches.
     *
     * @return void
     */
    public static function flushCache()
    {
        static::$snakeCache = [];
        static::$camelCache = [];
        static::$studlyCache = [];
    }
}
