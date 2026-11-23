<?php
namespace App\Services;

use InvalidArgumentException;
use JsonException;

final class ChatbotImportParser
{
    public const MAX_BYTES = 262144;
    public const MAX_ROWS = 100;

    private const FIELDS = [
        'stable_key',
        'question',
        'answer',
        'category',
        'keywords',
        'link_label',
        'link_path',
        'status',
        'sort_order',
    ];

    public static function parse(string $contents, string $format): array
    {
        if ($contents === '' || strlen($contents) > self::MAX_BYTES) {
            throw new InvalidArgumentException('The import file is empty or larger than 256 KB.');
        }
        if (!mb_check_encoding($contents, 'UTF-8')) {
            throw new InvalidArgumentException('Import files must use valid UTF-8 text.');
        }

        $rows = match (strtolower($format)) {
            'csv' => self::parseCsv($contents),
            'json' => self::parseJson($contents),
            default => throw new InvalidArgumentException('Choose a CSV or JSON file.'),
        };
        if (count($rows) > self::MAX_ROWS) {
            throw new InvalidArgumentException('Import files can contain at most 100 records.');
        }

        $result = [];
        foreach ($rows as $index => $row) {
            $result[] = self::validateRow($row, $index + 1);
        }

        return $result;
    }

    private static function parseCsv(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('The CSV import could not be opened.');
        }
        fwrite($stream, $contents);
        rewind($stream);
        $headers = fgetcsv($stream, self::MAX_BYTES + 1, ',', '"', '');
        if (!is_array($headers) || $headers === []) {
            fclose($stream);
            throw new InvalidArgumentException('The CSV file must include a header row.');
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(static fn ($header): string => strtolower(trim((string) $header)), $headers);
        if (count($headers) !== count(array_unique($headers))) {
            fclose($stream);
            throw new InvalidArgumentException('The CSV file contains duplicate column names.');
        }
        if (array_diff($headers, self::FIELDS) !== [] || !in_array('question', $headers, true) || !in_array('answer', $headers, true)) {
            fclose($stream);
            throw new InvalidArgumentException('CSV headers must include question and answer and use only the template columns.');
        }

        $rows = [];
        while (($values = fgetcsv($stream, self::MAX_BYTES + 1, ',', '"', '')) !== false) {
            if ($values === [null] || $values === []) continue;
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $values[$index] ?? '';
            }
            if (count($values) !== count($headers)) {
                $row['_row_error'] = 'The number of CSV values does not match the header columns.';
            }
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    private static function parseJson(string $contents): array
    {
        try {
            $decoded = json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new InvalidArgumentException('The JSON file is not valid JSON.', 0, $error);
        }
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $decoded = $decoded['items'];
        }
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new InvalidArgumentException('JSON must be an array of knowledge records or an object with an items array.');
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (!is_array($row) || array_is_list($row)) {
                $rows[] = ['_row_error' => 'Each JSON item must be an object.'];
                continue;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private static function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];
        if (isset($row['_row_error'])) $errors[] = (string) $row['_row_error'];
        $unknownFields = array_diff(array_keys($row), [...self::FIELDS, '_row_error']);
        if ($unknownFields !== []) $errors[] = 'Unsupported fields: ' . implode(', ', array_map('strval', $unknownFields)) . '.';
        foreach (array_intersect(array_keys($row), self::FIELDS) as $field) {
            if ($field !== 'keywords' && $row[$field] !== null && !is_scalar($row[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be a scalar value.';
            }
        }
        $question = trim(is_scalar($row['question'] ?? null) ? (string) $row['question'] : '');
        $answer = trim(is_scalar($row['answer'] ?? null) ? (string) $row['answer'] : '');
        $category = trim(is_scalar($row['category'] ?? null) ? (string) $row['category'] : 'general');
        $keywords = $row['keywords'] ?? '';
        if (is_array($keywords)) {
            if (array_filter($keywords, static fn ($value): bool => !is_scalar($value)) !== []) {
                $errors[] = 'Keywords must contain only text values.';
                $keywords = '';
            } else {
                $keywords = implode('|', array_map(static fn ($value): string => trim((string) $value), $keywords));
            }
        }
        $keywords = trim(is_scalar($keywords) ? (string) $keywords : '');
        $linkLabel = trim(is_scalar($row['link_label'] ?? null) ? (string) $row['link_label'] : '');
        $linkPath = trim(is_scalar($row['link_path'] ?? null) ? (string) $row['link_path'] : '');
        $status = trim(is_scalar($row['status'] ?? null) ? (string) $row['status'] : 'draft');
        $sortOrder = filter_var($row['sort_order'] ?? '0', FILTER_VALIDATE_INT);
        $stableKey = strtolower(trim(is_scalar($row['stable_key'] ?? null) ? (string) $row['stable_key'] : ''));

        if ($question === '' || strlen($question) > 255) $errors[] = 'Question is required and must be at most 255 characters.';
        if ($answer === '' || strlen($answer) > 12000) $errors[] = 'Answer is required and must be at most 12,000 characters.';
        if ($category === '' || strlen($category) > 80) $errors[] = 'Category is required and must be at most 80 characters.';
        if (strlen($keywords) > 1000) $errors[] = 'Keywords must be at most 1,000 characters.';
        if (strlen($linkLabel) > 100) $errors[] = 'Link label must be at most 100 characters.';
        if ($linkPath !== '' && !self::validInternalPath($linkPath)) $errors[] = 'Link path must be a local public page path.';
        if (!in_array($status, ['draft', 'published', 'archived'], true)) $errors[] = 'Status must be draft, published, or archived.';
        if ($sortOrder === false || $sortOrder < -1000 || $sortOrder > 1000) $errors[] = 'Sort order must be between -1000 and 1000.';
        if ($stableKey === '') {
            $stableKey = hash('sha256', strtolower($question));
        } elseif (!preg_match('/^[a-f0-9]{64}$/', $stableKey)) {
            $errors[] = 'Stable key must be a 64-character hexadecimal value.';
        }

        return [
            'row_number' => $rowNumber,
            'errors' => $errors,
            'data' => [
                'stable_key' => $stableKey,
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
                'keywords' => $keywords,
                'link_label' => $linkLabel,
                'link_path' => $linkPath,
                'status' => $status,
                'sort_order' => $sortOrder === false ? 0 : (int) $sortOrder,
            ],
        ];
    }

    private static function validInternalPath(string $path): bool
    {
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\x00-\x20]/', $path)) {
            return false;
        }
        if (in_array($path, ['/about', '/services', '/products', '/blog', '/contact', '/faq', '/login', '/register', '/account', '/account/orders', '/account/settings'], true)) {
            return true;
        }
        return preg_match('#^/(?:products|services|blog)/[a-z0-9-]+$#', $path) === 1
            || preg_match('#^/products/id/[1-9][0-9]*$#', $path) === 1;
    }
}
