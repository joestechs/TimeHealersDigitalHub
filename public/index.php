<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $publicRoot = realpath(__DIR__);
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($publicRoot !== false && is_string($requestPath)) {
        $requestedFile = realpath(
            $publicRoot . DIRECTORY_SEPARATOR
            . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rawurldecode($requestPath)), DIRECTORY_SEPARATOR)
        );
        $publicPrefix = strtolower(rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        $extension = $requestedFile === false ? '' : strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
        if (
            $requestedFile !== false
            && str_starts_with(strtolower($requestedFile), $publicPrefix)
            && is_file($requestedFile)
            && !in_array($extension, ['php', 'phtml'], true)
        ) {
            return false;
        }
    }
}

require dirname(__DIR__) . '/bootstrap/app.php';

