<?php

namespace App\Support;

class DigitalizeResponseNormalizer
{
    /**
     * Normalize structured extraction responses, especially doc payloads that are
     * accidentally wrapped in fenced JSON or nested {type, content} objects.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public static function normalize(array $response): array
    {
        $normalized = $response;

        $decodedContent = self::decodeJsonPayload($normalized['content'] ?? null);
        if (is_array($decodedContent) && isset($decodedContent['type'], $decodedContent['content'])) {
            $normalized = array_merge($normalized, $decodedContent);
        }

        if (($normalized['type'] ?? null) !== 'doc') {
            return $normalized;
        }

        $content = self::unwrapDocText($normalized['content'] ?? '');
        $pages = $normalized['doc_pages'] ?? null;
        if (is_array($pages) && $pages !== []) {
            $normalizedPages = array_values(array_map(
                fn ($page) => self::unwrapDocPage($page),
                $pages,
            ));
            $normalized['doc_pages'] = $normalizedPages;
            $normalized['doc_page_count'] = count($normalizedPages) ?: (int) ($normalized['doc_page_count'] ?? 1);
            $normalized['content'] = implode("\n\n", $normalizedPages);

            return $normalized;
        }

        $normalized['content'] = $content;

        return $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeJsonPayload(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/(\{.*\})/s', $trimmed, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private static function unwrapDocText(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = trim((string) $value);
        $decoded = self::decodeJsonPayload($text);
        if (! is_array($decoded)) {
            return $text;
        }

        if (isset($decoded['doc_pages']) && is_array($decoded['doc_pages']) && $decoded['doc_pages'] !== []) {
            return implode("\n\n", array_map(fn ($page) => self::unwrapDocPage($page), $decoded['doc_pages']));
        }

        if (isset($decoded['content']) && is_scalar($decoded['content'])) {
            return trim((string) $decoded['content']);
        }

        return $text;
    }

    private static function unwrapDocPage(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = trim((string) $value);
        $decoded = self::decodeJsonPayload($text);
        if (! is_array($decoded)) {
            return $text;
        }

        if (isset($decoded['doc_pages']) && is_array($decoded['doc_pages']) && $decoded['doc_pages'] !== []) {
            return self::unwrapDocPage($decoded['doc_pages'][0]);
        }

        if (isset($decoded['content']) && is_scalar($decoded['content'])) {
            return trim((string) $decoded['content']);
        }

        return $text;
    }
}
