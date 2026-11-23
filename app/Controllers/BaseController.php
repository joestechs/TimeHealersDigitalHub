<?php
namespace App\Controllers;

use PDO;

abstract class BaseController
{
    public function __construct(protected PDO $db) {}

    protected function render(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        extract($data);
        $viewFile = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
        require $viewFile;
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . appUrl($path));
        exit;
    }

    protected function requireCsrf(): void
    {
        $token = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(403);
            throw new \RuntimeException('Invalid security token', 403);
        }
    }

    protected function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function imageUrl(mixed $value): string
    {
        $value = trim((string) $value);
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (filter_var($value, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)) {
            return $value;
        }
        if (preg_match('#^/uploads/products/[a-f0-9]{32}\.(?:jpg|png|webp)$#i', $value)) {
            return appUrl($value);
        }

        return '';
    }

    protected function productUrl(array $product): string
    {
        $slug = (string) ($product['slug'] ?? '');
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return appUrl('/products/' . $slug);
        }

        return appUrl('/products/id/' . (int) ($product['id'] ?? 0));
    }
}
