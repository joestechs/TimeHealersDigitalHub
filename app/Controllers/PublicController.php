<?php
namespace App\Controllers;

use App\Services\ChatbotService;

class PublicController extends BaseController
{
    private function renderPage(string $title, string $view, array $data = [], int $status = 200): void
    {
        extract($data);
        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
        $pageContent = ob_get_clean();
        $this->render('layouts/page', compact('title', 'pageContent'), $status);
    }

    public function home(): void
    {
        $featured = $this->db->query(
            "SELECT p.*, c.name AS category_name FROM products p
             LEFT JOIN product_categories c ON c.id=p.category_id
            WHERE p.status='published' AND p.deleted_at IS NULL AND p.featured=1 ORDER BY p.created_at DESC LIMIT 6"
        )->fetchAll();
        $chatbot = new ChatbotService($this->db);
        $chatbotSettings = $chatbot->settings();
        $chatbotExternalProcessing = $chatbot->externalProcessingConsentRequired();
        $this->render('layouts/main', compact('featured', 'chatbotSettings', 'chatbotExternalProcessing'), 200);
    }

    public function simple(string $title, string $page): void
    {
        $this->renderPage($title, 'pages/' . $page);
    }

    public function products(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $sql = "SELECT p.*, c.name AS category_name FROM products p
                LEFT JOIN product_categories c ON c.id=p.category_id
                WHERE p.status='published' AND p.deleted_at IS NULL";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (p.name LIKE :search OR p.short_description LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        if ($category !== '') {
            $sql .= " AND c.slug=:category";
            $params['category'] = $category;
        }
        $sql .= " ORDER BY p.featured DESC, p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        $categories = $this->db->query("SELECT * FROM product_categories WHERE is_active=1 ORDER BY sort_order, name")->fetchAll();
        $this->renderPage('Digital Products', 'shop/index', compact('products', 'categories'));
    }

    public function product(string $slug): void
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN product_categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='published' AND p.deleted_at IS NULL");
        $stmt->execute([$slug]);
        $this->renderProduct($stmt->fetch());
    }

    public function productById(int $id): void
    {
        $stmt = $this->db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN product_categories c ON c.id=p.category_id WHERE p.id=? AND p.status='published' AND p.deleted_at IS NULL");
        $stmt->execute([$id]);
        $this->renderProduct($stmt->fetch());
    }

    private function renderProduct(array|false $product): void
    {
        if (!$product) $this->notFound();
        $this->renderPage($product['name'], 'shop/show', compact('product'));
    }

    public function services(): void
    {
        $services = $this->db->query("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order")->fetchAll();
        $this->renderPage('Services', 'pages/services', compact('services'));
    }

    public function service(string $slug): void
    {
        $stmt = $this->db->prepare("SELECT * FROM services WHERE slug=? AND is_active=1");
        $stmt->execute([$slug]);
        $service = $stmt->fetch();
        if (!$service) $this->notFound();
        $this->renderPage($service['name'], 'pages/service', compact('service'));
    }

    public function blog(): void
    {
        $posts = $this->db->query("SELECT id,title,slug,excerpt,cover_image,published_at FROM blog_posts WHERE status='published' ORDER BY published_at DESC")->fetchAll();
        $this->renderPage('Knowledge Hub', 'pages/blog', compact('posts'));
    }

    public function article(string $slug): void
    {
        $stmt = $this->db->prepare("SELECT title,slug,excerpt,content,cover_image,published_at FROM blog_posts WHERE slug=? AND status='published'");
        $stmt->execute([$slug]);
        $post = $stmt->fetch();
        if (!$post) $this->notFound();
        $this->renderPage($post['title'], 'pages/article', compact('post'));
    }

    public function checkout(): void
    {
        $this->renderPage('Checkout', 'pages/checkout');
    }

    public function contact(): void
    {
        $this->requireCsrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $service = trim((string) ($_POST['service'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || $message === '') {
            $_SESSION['flash_error'] = 'Please provide your name, a valid email, and a message.';
            $this->redirect('/contact');
        }
        $stmt = $this->db->prepare("INSERT INTO service_inquiries (name,email,phone,service_name,message,status,created_at) VALUES (?,?,?,?,?, 'new', NOW())");
        $stmt->execute([$name, $email, $phone, $service, $message]);
        $_SESSION['flash'] = 'Thank you. Your inquiry has been received.';
        $this->redirect('/contact');
    }

    public function newsletter(): void
    {
        $this->requireCsrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Please enter a valid email address.';
            $this->redirect('/');
        }
        $stmt = $this->db->prepare("INSERT IGNORE INTO newsletter_subscribers (email,status,subscribed_at) VALUES (?,'active',NOW())");
        $stmt->execute([$email]);
        $_SESSION['flash'] = 'You are subscribed.';
        $this->redirect('/');
    }

    public function notFound(): never
    {
        $this->renderPage('Page Not Found', 'pages/404', [], 404);
        exit;
    }
}
