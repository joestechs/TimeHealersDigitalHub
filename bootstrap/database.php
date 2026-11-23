<?php
declare(strict_types=1);

function envValue(string $key, mixed $default = null): mixed {
    static $values;
    if ($values === null) {
        $values = [];
        $envFile = dirname(__DIR__) . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$name, $value] = array_map('trim', explode('=', $line, 2));
                $values[$name] = trim($value, "\"'");
            }
        }
    }
    return $values[$key] ?? getenv($key) ?: $default;
}

function appBasePath(): string {
    $configured = trim((string) envValue('APP_BASE_PATH', ''));
    if ($configured !== '') {
        return '/' . trim($configured, '/');
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptName = preg_replace('#/public/index\.php$#', '', $scriptName);
    $scriptName = preg_replace('#/index\.php$#', '', $scriptName);
    return rtrim($scriptName, '/');
}

function appUrl(string $path = '/'): string {
    return appBasePath() . '/' . ltrim($path, '/');
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        envValue('DB_HOST', '127.0.0.1'),
        envValue('DB_PORT', '3306'),
        envValue('DB_DATABASE', 'thdh')
    );
    $pdo = new PDO($dsn, (string) envValue('DB_USERNAME', 'root'), (string) envValue('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $error) {
    http_response_code(503);
    require dirname(__DIR__) . '/resources/views/pages/setup.php';
    exit;
}

