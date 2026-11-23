<?php
declare(strict_types=1);

use App\Controllers\PublicController;
use App\Controllers\AccountController;
use App\Controllers\AdminController;
use App\Controllers\ChatbotController;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$base = appBasePath();
if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
    $path = substr($path, strlen($base)) ?: '/';
}
// Continue accepting old local URLs while redirect rules are refreshed.
if ($path === '/public' || str_starts_with($path, '/public/')) {
    $path = substr($path, strlen('/public')) ?: '/';
}
$path = rtrim($path, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$public = new PublicController($pdo);
$account = new AccountController($pdo);
$admin = new AdminController($pdo);
$chatbot = new ChatbotController($pdo);

try {
    if ($method === 'POST' && $path === '/chatbot/message') {
        $chatbot->message();
    } elseif ($method === 'POST' && $path === '/contact') {
        $public->contact();
    } elseif ($method === 'POST' && $path === '/newsletter') {
        $public->newsletter();
    } elseif ($method === 'POST' && $path === '/login') {
        $account->login();
    } elseif ($method === 'POST' && $path === '/register') {
        $account->register();
    } elseif ($method === 'POST' && $path === '/account/profile') {
        $account->updateProfile();
    } elseif ($method === 'POST' && $path === '/account/password') {
        $account->updatePassword();
    } elseif ($method === 'POST' && $path === '/logout') {
        $account->logout();
    } elseif ($method === 'POST' && $path === '/admin/products') {
        $admin->mutate('products', 'save');
    } elseif ($method === 'POST' && preg_match('#^/admin/(products|categories|vendors|customers|staff|inquiries|services|articles|newsletter)/(save|status|archive|restore)$#', $path, $matches)) {
        $admin->mutate($matches[1], $matches[2]);
    } elseif ($method === 'POST' && preg_match('#^/admin/chatbot/(settings|knowledge|test|archive|restore|import-preview|import-commit)$#', $path, $matches)) {
        $admin->mutate('chatbot', $matches[1]);
    } elseif (match ($path) {
        '/', '/about', '/services', '/products', '/blog', '/contact', '/faq',
        '/login', '/register',         '/account', '/account/orders', '/account/downloads', '/account/settings',
        '/admin', '/admin/products', '/admin/categories', '/admin/vendors', '/admin/customers',
        '/admin/staff', '/admin/inquiries', '/admin/services', '/admin/articles', '/admin/newsletter', '/admin/chatbot', '/admin/orders',
        '/checkout', '/404' => true,
        default => false,
    }) {
        match ($path) {
            '/' => $public->home(),
            '/about' => $public->simple('About Us', 'about'),
            '/services' => $public->services(),
            '/products' => $public->products(),
            '/blog' => $public->blog(),
            '/contact' => $public->simple('Contact Us', 'contact'),
            '/faq' => $public->simple('Frequently Asked Questions', 'faq'),
            '/login' => $account->loginPage(),
            '/register' => $account->registerPage(),
            '/account', '/account/orders', '/account/downloads', '/account/settings' => $account->dashboard($path),
            '/checkout' => $public->checkout(),
            '/admin', '/admin/products', '/admin/categories', '/admin/vendors', '/admin/customers',
            '/admin/staff', '/admin/inquiries', '/admin/services', '/admin/articles', '/admin/newsletter', '/admin/chatbot', '/admin/orders' => $admin->dashboard($path),
            '/404' => $public->notFound(),
        };
    } elseif (preg_match('#^/products/id/([1-9][0-9]*)$#', $path, $matches)) {
        $public->productById((int) $matches[1]);
    } elseif (preg_match('#^/products/(.+)$#', $path, $matches)) {
        $public->product($matches[1]);
    } elseif (preg_match('#^/services/([a-z0-9-]+)$#', $path, $matches)) {
        $public->service($matches[1]);
    } elseif (preg_match('#^/blog/([a-z0-9-]+)$#', $path, $matches)) {
        $public->article($matches[1]);
    } else {
        $public->notFound();
    }
} catch (Throwable $error) {
    error_log($error->getMessage());
    if ($error->getCode() === 403) {
        http_response_code(403);
        require dirname(__DIR__) . '/resources/views/pages/403.php';
    } else {
        http_response_code(500);
        require dirname(__DIR__) . '/resources/views/pages/500.php';
    }
}
