<?php
namespace App\Controllers;

use App\Services\ChatbotImportParser;
use App\Services\ChatbotProviderException;
use App\Services\ChatbotService;
use finfo;
use InvalidArgumentException;
use Throwable;

class AdminController extends BaseController
{
    private const SECTIONS = [
        '/admin' => 'overview',
        '/admin/products' => 'products',
        '/admin/categories' => 'categories',
        '/admin/vendors' => 'vendors',
        '/admin/customers' => 'customers',
        '/admin/staff' => 'staff',
        '/admin/inquiries' => 'inquiries',
        '/admin/services' => 'services',
        '/admin/articles' => 'articles',
        '/admin/newsletter' => 'newsletter',
        '/admin/chatbot' => 'chatbot',
        '/admin/orders' => 'orders',
    ];

    private const FULFILLMENT_MODES = ['external_vendor', 'thdh_paypal', 'inquiry_required', 'free_download'];

    private function authorize(bool $superAdminOnly = false): array
    {
        $userId = filter_var($_SESSION['user']['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId) {
            $this->redirect('/login');
        }

        $stmt = $this->db->prepare("SELECT id,name,email,role,status FROM users WHERE id=? AND status='active' AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $admin = $stmt->fetch();
        if (!$admin || !in_array($admin['role'], ['super_admin', 'administrator'], true)) {
            unset($_SESSION['user']);
            $_SESSION['flash_error'] = 'An active administrator account is required.';
            $this->redirect('/login');
        }
        if ($superAdminOnly && $admin['role'] !== 'super_admin') {
            $_SESSION['flash_error'] = 'Only a super administrator can manage staff roles.';
            $this->redirect('/admin');
        }

        $_SESSION['user'] = $admin;
        return $admin;
    }

    public function dashboard(string $path): void
    {
        if (!isset(self::SECTIONS[$path])) {
            $this->redirect('/admin');
        }

        $section = self::SECTIONS[$path];
        $admin = $this->authorize($section === 'staff');
        $sectionData = $this->sectionData($section);
        ob_start();
        $this->render('admin/dashboard', compact('section', 'sectionData', 'admin'));
        $pageContent = ob_get_clean();
        $title = 'Administration - ' . ucfirst($section);
        $this->render('layouts/admin', compact('title', 'pageContent'));
    }

    public function mutate(string $section, string $action): void
    {
        if (!in_array($section, ['products', 'categories', 'vendors', 'customers', 'staff', 'inquiries', 'services', 'articles', 'newsletter', 'chatbot'], true)) {
            $this->redirect('/admin');
        }

        $admin = $this->authorize($section === 'staff');
        $this->requireCsrf();

        $handler = match ($section . ':' . $action) {
            'products:save' => fn () => $this->saveProduct($admin),
            'products:archive' => fn () => $this->archiveProduct($admin),
            'products:restore' => fn () => $this->restoreProduct($admin),
            'categories:save' => fn () => $this->saveCategory($admin),
            'vendors:save' => fn () => $this->saveVendor($admin),
            'customers:save' => fn () => $this->saveCustomer($admin),
            'staff:save' => fn () => $this->saveStaff($admin),
            'inquiries:status' => fn () => $this->changeStatus($admin, 'inquiries'),
            'services:save' => fn () => $this->saveService($admin),
            'articles:save' => fn () => $this->saveArticle($admin),
            'newsletter:save' => fn () => $this->saveNewsletter($admin),
            'newsletter:status' => fn () => $this->changeStatus($admin, 'newsletter'),
            'chatbot:settings' => fn () => $this->saveChatbotSettings($admin),
            'chatbot:knowledge' => fn () => $this->saveChatbotKnowledge($admin),
            'chatbot:test' => fn () => $this->testChatbot(),
            'chatbot:archive' => fn () => $this->archiveChatbotKnowledge($admin),
            'chatbot:restore' => fn () => $this->restoreChatbotKnowledge($admin),
            'chatbot:import-preview' => fn () => $this->previewChatbotImport(),
            'chatbot:import-commit' => fn () => $this->commitChatbotImport($admin),
            default => null,
        };
        if ($handler === null) {
            $this->redirect('/admin');
        }
        $handler();
    }

    private function sectionData(string $section): array
    {
        return match ($section) {
            'overview' => [
                'stats' => [
                    'customers' => (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='active' AND deleted_at IS NULL")->fetchColumn(),
                    'products' => (int) $this->db->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL")->fetchColumn(),
                    'inquiries' => (int) $this->db->query("SELECT COUNT(*) FROM service_inquiries WHERE status='new'")->fetchColumn(),
                    'orders' => (int) $this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
                    'published' => (int) $this->db->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn(),
                ],
                'recentInquiries' => $this->db->query('SELECT id,name,email,service_name,status,created_at FROM service_inquiries ORDER BY created_at DESC LIMIT 6')->fetchAll(),
                'recentOrders' => $this->db->query('SELECT o.id,o.status,o.subtotal,o.currency,o.created_at,u.name AS customer_name FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 6')->fetchAll(),
            ],
            'products' => $this->productData(),
            'categories' => [
                'records' => $this->db->query('SELECT * FROM product_categories ORDER BY sort_order,name')->fetchAll(),
                'edit' => $this->recordForEdit('product_categories'),
            ],
            'vendors' => [
                'records' => $this->db->query('SELECT * FROM vendors ORDER BY created_at DESC,id DESC')->fetchAll(),
                'edit' => $this->recordForEdit('vendors'),
            ],
            'customers' => $this->customerData(),
            'staff' => [
                'records' => $this->db->query("SELECT id,name,email,role,status,created_at FROM users WHERE role IN ('super_admin','administrator') AND deleted_at IS NULL ORDER BY role,name")->fetchAll(),
                'candidates' => $this->db->query("SELECT id,name,email FROM users WHERE role='customer' AND status='active' AND deleted_at IS NULL ORDER BY name")->fetchAll(),
            ],
            'inquiries' => ['records' => $this->db->query('SELECT * FROM service_inquiries ORDER BY FIELD(status,"new","in_progress","responded","closed"),created_at DESC LIMIT 200')->fetchAll()],
            'services' => [
                'records' => $this->db->query('SELECT * FROM services ORDER BY sort_order,name')->fetchAll(),
                'edit' => $this->recordForEdit('services'),
            ],
            'articles' => [
                'records' => $this->db->query("SELECT id,title,slug,excerpt,cover_image,status,published_at FROM blog_posts ORDER BY COALESCE(published_at,'1000-01-01') DESC,id DESC")->fetchAll(),
                'edit' => $this->recordForEdit('blog_posts'),
            ],
            'newsletter' => [
                'records' => $this->db->query('SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC LIMIT 500')->fetchAll(),
                'edit' => $this->recordForEdit('newsletter_subscribers'),
            ],
            'chatbot' => $this->chatbotData(),
            'orders' => $this->orderData(),
            default => [],
        };
    }

    private function chatbotData(): array
    {
        $chatbot = new ChatbotService($this->db);
        $settings = $chatbot->settings();
        $externalProcessing = $chatbot->externalProcessingConsentRequired();
        $testResult = $_SESSION['chatbot_test_result'] ?? null;
        unset($_SESSION['chatbot_test_result']);
        $query = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT id,question,category,status,source_type,sort_order,updated_at
                FROM chatbot_knowledge';
        $params = [];
        if ($query !== '') {
            $sql .= ' WHERE question LIKE :question OR category LIKE :category OR keywords LIKE :keywords';
            $params = [
                'question' => '%' . $query . '%',
                'category' => '%' . $query . '%',
                'keywords' => '%' . $query . '%',
            ];
        }
        $sql .= " ORDER BY FIELD(status,'published','draft','archived'),sort_order,question LIMIT 300";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        $edit = $this->recordForEdit('chatbot_knowledge');
        $importPreview = $_SESSION['chatbot_import_preview'] ?? null;
        if (
            !is_array($importPreview)
            || !isset($importPreview['created_at'])
            || (int) $importPreview['created_at'] < time() - 900
        ) {
            unset($_SESSION['chatbot_import_preview']);
            $importPreview = null;
        } else {
            $seen = [];
            $lookup = $this->db->prepare('SELECT id,question,status FROM chatbot_knowledge WHERE stable_key=?');
            foreach ($importPreview['rows'] as &$row) {
                if ($row['errors'] !== []) continue;
                $key = $row['data']['stable_key'];
                $lookup->execute([$key]);
                $existing = $lookup->fetch();
                $row['duplicate_id'] = $existing['id'] ?? null;
                $row['existing_question'] = $existing['question'] ?? null;
                $row['existing_status'] = $existing['status'] ?? null;
                $row['duplicate_in_file'] = isset($seen[$key]);
                $seen[$key] = true;
            }
            unset($row);
        }

        return compact('settings', 'records', 'edit', 'query', 'importPreview', 'externalProcessing', 'testResult');
    }

    private function productData(): array
    {
        $categories = $this->db->query('SELECT id,name,is_active FROM product_categories ORDER BY sort_order,name')->fetchAll();
        $vendors = $this->db->query('SELECT id,name,status FROM vendors ORDER BY name')->fetchAll();
        $query = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT p.*,c.name AS category_name,v.name AS vendor_name FROM products p
                LEFT JOIN product_categories c ON c.id=p.category_id
                LEFT JOIN vendors v ON v.id=p.vendor_id';
        $params = [];
        if ($query !== '') {
            $sql .= ' WHERE p.name LIKE :name OR p.slug LIKE :slug';
            $params = ['name' => '%' . $query . '%', 'slug' => '%' . $query . '%'];
        }
        $sql .= ' ORDER BY p.deleted_at IS NOT NULL,p.created_at DESC LIMIT 200';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        $edit = null;
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $this->db->prepare('SELECT * FROM products WHERE id=?');
            $stmt->execute([$id]);
            $edit = $stmt->fetch() ?: null;
        }

        return compact('categories', 'vendors', 'records', 'edit', 'query');
    }

    private function customerData(): array
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $sql = "SELECT id,name,email,status,created_at FROM users WHERE role='customer' AND deleted_at IS NULL";
        $params = [];
        if ($query !== '') {
            $sql .= ' AND (name LIKE :name OR email LIKE :email)';
            $params = ['name' => '%' . $query . '%', 'email' => '%' . $query . '%'];
        }
        $sql .= ' ORDER BY created_at DESC LIMIT 200';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        $edit = null;
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $this->db->prepare("SELECT id,name,email,status FROM users WHERE id=? AND role='customer' AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $edit = $stmt->fetch() ?: null;
        }

        return compact('records', 'edit', 'query');
    }

    private function recordForEdit(string $table): ?array
    {
        $allowedTables = ['product_categories', 'vendors', 'services', 'blog_posts', 'newsletter_subscribers', 'chatbot_knowledge'];
        if (!in_array($table, $allowedTables, true)) throw new \LogicException('Unsupported edit table.');
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) return null;
        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private function orderData(): array
    {
        $records = $this->db->query(
            'SELECT o.id,o.user_id,o.order_type,o.status,o.subtotal,o.currency,o.created_at,
                    u.name AS customer_name,u.email AS customer_email,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS item_count
             FROM orders o LEFT JOIN users u ON u.id=o.user_id
             ORDER BY o.created_at DESC LIMIT 200'
        )->fetchAll();
        $order = null;
        $items = [];
        $payments = [];
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $this->db->prepare('SELECT o.id,o.order_type,o.status,o.subtotal,o.currency,o.created_at,u.name AS customer_name,u.email AS customer_email FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=?');
            $stmt->execute([$id]);
            $order = $stmt->fetch() ?: null;
            if ($order) {
                $stmt = $this->db->prepare('SELECT oi.quantity,oi.unit_price,p.name AS product_name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?');
                $stmt->execute([$id]);
                $items = $stmt->fetchAll();
                $stmt = $this->db->prepare('SELECT provider,provider_reference,amount,currency,status,created_at FROM payments WHERE order_id=? ORDER BY created_at DESC');
                $stmt->execute([$id]);
                $payments = $stmt->fetchAll();
            }
        }

        return compact('records', 'order', 'items', 'payments');
    }

    private function saveProduct(array $admin): void
    {
        $id = $this->postId('id', 'products');
        $existing = null;
        if ($id !== null) {
            $stmt = $this->db->prepare('SELECT * FROM products WHERE id=?');
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) $this->invalid('products', 'The selected product no longer exists.');
            if ($existing['deleted_at'] !== null) $this->invalid('products', 'Restore the archived product before editing it.');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = $this->slug((string) ($_POST['slug'] ?? ''), $name);
        $shortDescription = trim((string) ($_POST['short_description'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = trim((string) ($_POST['price'] ?? ''));
        $compareAtPrice = trim((string) ($_POST['compare_at_price'] ?? ''));
        $currency = strtoupper(trim((string) ($_POST['currency'] ?? 'USD')));
        $mode = (string) ($_POST['fulfillment_mode'] ?? '');
        $checkoutUrl = trim((string) ($_POST['checkout_url'] ?? ''));
        $licenseType = trim((string) ($_POST['license_type'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'draft');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $categoryId = $this->postId('category_id', 'products');
        $vendorId = $this->postId('vendor_id', 'products');

        if ($name === '' || strlen($name) > 200 || !$this->validSlug($slug) || strlen($slug) > 220) {
            $this->invalid('products', 'Enter a product name and a valid URL slug.');
        }
        if (!$this->uniqueSlug('products', $slug, $id)) {
            $this->invalid('products', 'That product URL slug is already in use.');
        }
        if (!is_numeric($price) || !is_finite((float) $price) || (float) $price < 0 || (float) $price > 9999999999.99) {
            $this->invalid('products', 'Enter a valid product price.');
        }
        if ($compareAtPrice !== '' && (!is_numeric($compareAtPrice) || !is_finite((float) $compareAtPrice) || (float) $compareAtPrice < 0 || (float) $compareAtPrice > 9999999999.99)) {
            $this->invalid('products', 'Enter a valid comparison price or leave it blank.');
        }
        if (!preg_match('/^[A-Z]{3}$/', $currency) || !in_array($mode, self::FULFILLMENT_MODES, true) || !in_array($status, ['draft', 'published'], true)) {
            $this->invalid('products', 'Choose a valid currency, fulfillment method, and publication status.');
        }
        if ($checkoutUrl !== '' && (strlen($checkoutUrl) > 1000 || !$this->validHttpUrl($checkoutUrl))) {
            $this->invalid('products', 'Checkout links must be valid HTTP or HTTPS URLs.');
        }
        $this->assertCatalogReference('product_categories', $categoryId, 'products', 'Choose an existing category.');
        $this->assertCatalogReference('vendors', $vendorId, 'products', 'Choose an existing vendor.');
        if (strlen($shortDescription) > 2000 || strlen($description) > 20000 || strlen($licenseType) > 100) {
            $this->invalid('products', 'One or more product fields are too long.');
        }

        $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
        $hasUpload = isset($_FILES['image']) && (int) $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;
        if ($hasUpload && $imageUrl !== '') {
            $this->invalid('products', 'Choose an image URL or upload an image, not both.');
        }
        if ($imageUrl !== '' && (strlen($imageUrl) > 500 || !$this->validHttpUrl($imageUrl))) {
            $this->invalid('products', 'Image links must be valid HTTP or HTTPS URLs.');
        }

        $uploaded = null;
        try {
            if ($hasUpload) {
                $uploaded = $this->storeProductImage($_FILES['image']);
                $thumbnail = $uploaded['path'];
            } elseif ($imageUrl !== '') {
                $thumbnail = $imageUrl;
            } elseif (isset($_POST['remove_image'])) {
                $thumbnail = null;
            } else {
                $thumbnail = $existing['thumbnail'] ?? null;
            }

            $this->auditedChange((int) $admin['id'], $id === null ? 'created' : 'updated', 'product', function () use (
                $id, $categoryId, $vendorId, $name, $slug, $shortDescription, $description, $thumbnail,
                $price, $compareAtPrice, $currency, $mode, $checkoutUrl, $licenseType, $status, $featured
            ): int {
                $values = [
                    $categoryId, $vendorId, $name, $slug, $shortDescription, $description, $thumbnail,
                    (float) $price, $compareAtPrice === '' ? null : (float) $compareAtPrice,
                    $currency, $mode, $checkoutUrl === '' ? null : $checkoutUrl,
                    $licenseType === '' ? null : $licenseType, $status, $featured,
                ];
                if ($id === null) {
                    $stmt = $this->db->prepare(
                        'INSERT INTO products (category_id,vendor_id,name,slug,short_description,description,thumbnail,price,compare_at_price,currency,fulfillment_mode,checkout_url,license_type,status,featured,created_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())'
                    );
                    $stmt->execute($values);
                    return (int) $this->db->lastInsertId();
                }

                $stmt = $this->db->prepare(
                    'UPDATE products SET category_id=?,vendor_id=?,name=?,slug=?,short_description=?,description=?,thumbnail=?,price=?,compare_at_price=?,currency=?,fulfillment_mode=?,checkout_url=?,license_type=?,status=?,featured=?,updated_at=NOW() WHERE id=? AND deleted_at IS NULL'
                );
                $stmt->execute([...$values, $id]);
                return $id;
            });
        } catch (Throwable $error) {
            if ($uploaded && is_file($uploaded['absolute'])) {
                unlink($uploaded['absolute']);
            }
            throw $error;
        }

        $_SESSION['flash'] = 'Product saved.';
        $this->redirect('/admin/products');
    }

    private function storeProductImage(array $upload): array
    {
        if ((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
            $this->invalid('products', 'The image upload did not complete successfully.');
        }
        if ((int) ($upload['size'] ?? 0) <= 0 || (int) $upload['size'] > 5 * 1024 * 1024) {
            $this->invalid('products', 'Choose an image smaller than 5 MB.');
        }
        if (!class_exists(finfo::class)) {
            throw new \RuntimeException('Image validation requires the PHP fileinfo extension.');
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file((string) $upload['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $imageSize = getimagesize((string) $upload['tmp_name']);
        if (!is_string($mime) || !isset($extensions[$mime]) || $imageSize === false) {
            $this->invalid('products', 'Upload a valid JPEG, PNG, or WebP image.');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/products';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('The product image directory could not be created.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $absolute = $directory . '/' . $filename;
        if (!move_uploaded_file((string) $upload['tmp_name'], $absolute)) {
            throw new \RuntimeException('The product image could not be saved.');
        }

        return ['path' => '/uploads/products/' . $filename, 'absolute' => $absolute];
    }

    private function archiveProduct(array $admin): void
    {
        $id = $this->requiredPostId('id', 'products');
        $this->assertRecordExists('products', $id, 'products', 'deleted_at IS NULL');
        $this->auditedChange((int) $admin['id'], 'archived', 'product', function () use ($id): int {
            $stmt = $this->db->prepare("UPDATE products SET status='archived',deleted_at=NOW(),updated_at=NOW() WHERE id=? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            return $id;
        });
        $_SESSION['flash'] = 'Product archived. It is no longer visible in the marketplace.';
        $this->redirect('/admin/products');
    }

    private function restoreProduct(array $admin): void
    {
        $id = $this->requiredPostId('id', 'products');
        $this->assertRecordExists('products', $id, 'products', 'deleted_at IS NOT NULL');
        $this->auditedChange((int) $admin['id'], 'restored', 'product', function () use ($id): int {
            $stmt = $this->db->prepare("UPDATE products SET status='draft',deleted_at=NULL,updated_at=NOW() WHERE id=?");
            $stmt->execute([$id]);
            return $id;
        });
        $_SESSION['flash'] = 'Product restored as a draft.';
        $this->redirect('/admin/products');
    }

    private function saveCategory(array $admin): void
    {
        $id = $this->postId('id', 'categories');
        if ($id !== null) $this->assertRecordExists('product_categories', $id, 'categories');
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = $this->slug((string) ($_POST['slug'] ?? ''), $name);
        $description = trim((string) ($_POST['description'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'active');
        $sortOrder = filter_var($_POST['sort_order'] ?? '0', FILTER_VALIDATE_INT);
        if ($name === '' || strlen($name) > 150 || !$this->validSlug($slug) || strlen($slug) > 190) {
            $this->invalid('categories', 'Enter a category name and valid URL slug.');
        }
        if (!$this->uniqueSlug('product_categories', $slug, $id)) $this->invalid('categories', 'That category URL slug is already in use.');
        if (!in_array($status, ['active', 'inactive'], true) || $sortOrder === false || strlen($description) > 5000) {
            $this->invalid('categories', 'Check the category status, order, and description.');
        }
        $this->auditedChange((int) $admin['id'], $id === null ? 'created' : 'updated', 'product_category', function () use ($id, $name, $slug, $description, $status, $sortOrder): int {
            $active = $status === 'active' ? 1 : 0;
            if ($id === null) {
                $stmt = $this->db->prepare('INSERT INTO product_categories (name,slug,description,is_active,sort_order) VALUES (?,?,?,?,?)');
                $stmt->execute([$name, $slug, $description, $active, $sortOrder]);
                return (int) $this->db->lastInsertId();
            }
            $stmt = $this->db->prepare('UPDATE product_categories SET name=?,slug=?,description=?,is_active=?,sort_order=? WHERE id=?');
            $stmt->execute([$name, $slug, $description, $active, $sortOrder, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Product category saved.';
        $this->redirect('/admin/categories');
    }

    private function saveVendor(array $admin): void
    {
        $id = $this->postId('id', 'vendors');
        if ($id !== null) $this->assertRecordExists('vendors', $id, 'vendors');
        $name = trim((string) ($_POST['name'] ?? ''));
        $website = trim((string) ($_POST['website_url'] ?? ''));
        $platform = trim((string) ($_POST['platform_type'] ?? ''));
        $identifier = trim((string) ($_POST['account_identifier'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'active');
        if ($name === '' || strlen($name) > 150 || $platform === '' || strlen($platform) > 50 || strlen($identifier) > 190) {
            $this->invalid('vendors', 'Enter a vendor name and platform; check the field lengths.');
        }
        if ($website !== '' && !$this->validHttpUrl($website)) $this->invalid('vendors', 'Vendor website must be a valid HTTP or HTTPS URL.');
        if (!in_array($status, ['active', 'inactive'], true)) $this->invalid('vendors', 'Choose an active or inactive status.');
        $this->auditedChange((int) $admin['id'], $id === null ? 'created' : 'updated', 'vendor', function () use ($id, $name, $website, $platform, $identifier, $status): int {
            if ($id === null) {
                $stmt = $this->db->prepare("INSERT INTO vendors (name,website_url,platform_type,account_identifier,integration_type,status,created_at) VALUES (?,?,?,?, 'manual',?,NOW())");
                $stmt->execute([$name, $website ?: null, $platform, $identifier ?: null, $status]);
                return (int) $this->db->lastInsertId();
            }
            $stmt = $this->db->prepare('UPDATE vendors SET name=?,website_url=?,platform_type=?,account_identifier=?,status=? WHERE id=?');
            $stmt->execute([$name, $website ?: null, $platform, $identifier ?: null, $status, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Vendor saved.';
        $this->redirect('/admin/vendors');
    }

    private function saveCustomer(array $admin): void
    {
        $id = $this->requiredPostId('id', 'customers');
        $this->assertRecordExists('users', $id, 'customers', "role='customer' AND deleted_at IS NULL");
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $status = (string) ($_POST['status'] ?? '');
        if ($name === '' || strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $this->invalid('customers', 'Enter a valid customer name and email address.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) $this->invalid('customers', 'Choose an active or inactive account status.');
        $duplicate = $this->db->prepare('SELECT id FROM users WHERE email=? AND id<>?');
        $duplicate->execute([$email, $id]);
        if ($duplicate->fetch()) $this->invalid('customers', 'That email address is already associated with another account.');

        $this->auditedChange((int) $admin['id'], 'updated', 'customer', function () use ($id, $name, $email, $status): int {
            $stmt = $this->db->prepare("UPDATE users SET name=?,email=?,status=?,updated_at=NOW() WHERE id=? AND role='customer' AND deleted_at IS NULL");
            $stmt->execute([$name, $email, $status, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Customer profile saved.';
        $this->redirect('/admin/customers');
    }

    private function saveStaff(array $admin): void
    {
        $id = $this->requiredPostId('id', 'staff');
        if ($id === (int) $admin['id']) $this->invalid('staff', 'Sign in with another super administrator to change your own role or status.');
        $this->assertRecordExists('users', $id, 'staff', "deleted_at IS NULL");
        $role = (string) ($_POST['role'] ?? '');
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($role, ['customer', 'administrator', 'super_admin'], true) || !in_array($status, ['active', 'inactive'], true)) {
            $this->invalid('staff', 'Choose a valid account role and status.');
        }
        $stmt = $this->db->prepare('SELECT role,status FROM users WHERE id=?');
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        if (!$current || !in_array($current['role'], ['customer', 'administrator', 'super_admin'], true)) {
            $this->invalid('staff', 'The selected account cannot be managed as staff.');
        }
        if ($current['role'] === 'super_admin' && $current['status'] === 'active' && ($role !== 'super_admin' || $status !== 'active')) {
            $activeSuperAdmins = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role='super_admin' AND status='active' AND deleted_at IS NULL")->fetchColumn();
            if ($activeSuperAdmins <= 1) $this->invalid('staff', 'The last active super administrator cannot be demoted or deactivated.');
        }

        $this->auditedChange((int) $admin['id'], 'role_or_status_changed', 'user', function () use ($id, $role, $status): int {
            $stmt = $this->db->prepare('UPDATE users SET role=?,status=?,updated_at=NOW() WHERE id=? AND deleted_at IS NULL');
            $stmt->execute([$role, $status, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Account role and status saved.';
        $this->redirect('/admin/staff');
    }

    private function saveChatbotSettings(array $admin): void
    {
        $enabled = isset($_POST['is_enabled']) ? 1 : 0;
        $name = trim((string) ($_POST['bot_name'] ?? ''));
        $greeting = trim((string) ($_POST['greeting'] ?? ''));
        $fallback = trim((string) ($_POST['fallback_message'] ?? ''));
        $supportPath = trim((string) ($_POST['support_path'] ?? '/contact'));
        if ($name === '' || strlen($name) > 80 || $greeting === '' || strlen($greeting) > 500 || $fallback === '' || strlen($fallback) > 500) {
            $this->invalid('chatbot', 'Enter a chatbot name, greeting, and fallback message within the stated limits.');
        }
        if (!$this->validChatbotPath($supportPath)) {
            $this->invalid('chatbot', 'The fallback link must be a local public page path.');
        }

        $this->auditedChange((int) $admin['id'], 'updated', 'chatbot_settings', function () use ($admin, $enabled, $name, $greeting, $fallback, $supportPath): int {
            $stmt = $this->db->prepare(
                'UPDATE chatbot_settings SET is_enabled=?,bot_name=?,greeting=?,fallback_message=?,support_path=?,updated_by=?,updated_at=NOW() WHERE id=1'
            );
            $stmt->execute([$enabled, $name, $greeting, $fallback, $supportPath, (int) $admin['id']]);
            if ($stmt->rowCount() === 0 && !$this->db->query('SELECT id FROM chatbot_settings WHERE id=1')->fetchColumn()) {
                throw new \RuntimeException('Chatbot settings row is missing; rerun the chatbot seed SQL file.');
            }
            return 1;
        });
        $_SESSION['flash'] = 'Chatbot settings saved.';
        $this->redirect('/admin/chatbot');
    }

    private function saveChatbotKnowledge(array $admin): void
    {
        $id = $this->postId('id', 'chatbot');
        if ($id !== null) $this->assertRecordExists('chatbot_knowledge', $id, 'chatbot');
        $question = trim((string) ($_POST['question'] ?? ''));
        $answer = trim((string) ($_POST['answer'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'general'));
        $keywords = trim((string) ($_POST['keywords'] ?? ''));
        $linkLabel = trim((string) ($_POST['link_label'] ?? ''));
        $linkPath = trim((string) ($_POST['link_path'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'draft');
        $sortOrder = filter_var($_POST['sort_order'] ?? '0', FILTER_VALIDATE_INT);
        if ($question === '' || strlen($question) > 255 || $answer === '' || strlen($answer) > 12000 || $category === '' || strlen($category) > 80) {
            $this->invalid('chatbot', 'Enter a question, answer, and category within the stated limits.');
        }
        if (strlen($keywords) > 1000 || strlen($linkLabel) > 100 || !in_array($status, ['draft', 'published'], true) || $sortOrder === false || $sortOrder < -1000 || $sortOrder > 1000) {
            $this->invalid('chatbot', 'Check the keywords, link label, status, and sort order.');
        }
        if ($linkPath !== '' && !$this->validChatbotPath($linkPath)) {
            $this->invalid('chatbot', 'The knowledge link must be a local public page path.');
        }

        if ($id === null) {
            $stableKey = hash('sha256', strtolower($question));
            $duplicate = $this->db->prepare('SELECT id FROM chatbot_knowledge WHERE stable_key=?');
            $duplicate->execute([$stableKey]);
            if ($duplicate->fetchColumn()) $this->invalid('chatbot', 'A knowledge record with this question already exists.');
        } else {
            $stmt = $this->db->prepare('SELECT stable_key FROM chatbot_knowledge WHERE id=?');
            $stmt->execute([$id]);
            $stableKey = (string) $stmt->fetchColumn();
        }

        $this->auditedChange((int) $admin['id'], $id === null ? 'created' : 'updated', 'chatbot_knowledge', function () use ($id, $stableKey, $question, $answer, $category, $keywords, $linkLabel, $linkPath, $status, $sortOrder, $admin): int {
            if ($id === null) {
                $stmt = $this->db->prepare(
                    'INSERT INTO chatbot_knowledge (stable_key,question,answer,category,keywords,link_label,link_path,status,sort_order,source_type,created_by,created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())'
                );
                $stmt->execute([$stableKey, $question, $answer, $category, $keywords ?: null, $linkLabel ?: null, $linkPath ?: null, $status, $sortOrder, 'manual', (int) $admin['id']]);
                return (int) $this->db->lastInsertId();
            }
            $stmt = $this->db->prepare(
                'UPDATE chatbot_knowledge SET question=?,answer=?,category=?,keywords=?,link_label=?,link_path=?,status=?,sort_order=?,updated_at=NOW()
                 WHERE id=? AND status<>\'archived\''
            );
            $stmt->execute([$question, $answer, $category, $keywords ?: null, $linkLabel ?: null, $linkPath ?: null, $status, $sortOrder, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Chatbot knowledge saved.';
        $this->redirect('/admin/chatbot');
    }

    private function testChatbot(): void
    {
        $message = trim((string) ($_POST['test_message'] ?? ''));
        if ($message === '' || !mb_check_encoding($message, 'UTF-8') || mb_strlen($message, 'UTF-8') > 1000) {
            $this->invalid('chatbot', 'Enter a test question of up to 1,000 characters.');
        }
        $chatbot = new ChatbotService($this->db);
        if ($chatbot->externalProcessingConsentRequired() && (string) ($_POST['consent'] ?? '') !== '1') {
            $this->invalid('chatbot', 'Confirm the external-provider notice before testing with the configured provider.');
        }
        try {
            $result = $chatbot->respond($message);
        } catch (ChatbotProviderException $error) {
            error_log('Chatbot admin test failed: ' . $error->getMessage());
            $_SESSION['flash_error'] = 'The configured provider test failed. Check the server log and provider settings.';
            $this->redirect('/admin/chatbot');
        }
        $_SESSION['chatbot_test_result'] = [
            'answer' => (string) $result['answer'],
            'action' => $result['action'],
            'mode' => (string) $result['mode'],
        ];
        $this->redirect('/admin/chatbot');
    }

    private function archiveChatbotKnowledge(array $admin): void
    {
        $id = $this->requiredPostId('id', 'chatbot');
        $this->assertRecordExists('chatbot_knowledge', $id, 'chatbot', "status<>'archived'");
        $this->auditedChange((int) $admin['id'], 'archived', 'chatbot_knowledge', function () use ($id): int {
            $stmt = $this->db->prepare("UPDATE chatbot_knowledge SET status='archived',updated_at=NOW() WHERE id=?");
            $stmt->execute([$id]);
            return $id;
        });
        $_SESSION['flash'] = 'Knowledge record archived.';
        $this->redirect('/admin/chatbot');
    }

    private function restoreChatbotKnowledge(array $admin): void
    {
        $id = $this->requiredPostId('id', 'chatbot');
        $this->assertRecordExists('chatbot_knowledge', $id, 'chatbot', "status='archived'");
        $this->auditedChange((int) $admin['id'], 'restored_as_draft', 'chatbot_knowledge', function () use ($id): int {
            $stmt = $this->db->prepare("UPDATE chatbot_knowledge SET status='draft',updated_at=NOW() WHERE id=? AND status='archived'");
            $stmt->execute([$id]);
            return $id;
        });
        $_SESSION['flash'] = 'Knowledge record restored as a draft.';
        $this->redirect('/admin/chatbot');
    }

    private function previewChatbotImport(): void
    {
        $file = $_FILES['knowledge_file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            $this->invalid('chatbot', 'Choose a valid CSV or JSON file to preview.');
        }
        $format = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($format, ['csv', 'json'], true)) $this->invalid('chatbot', 'Choose a CSV or JSON file.');
        $size = filesize((string) $file['tmp_name']);
        if ($size === false || $size > ChatbotImportParser::MAX_BYTES) {
            $this->invalid('chatbot', 'Import files must be no larger than 256 KB.');
        }
        $contents = file_get_contents((string) $file['tmp_name']);
        if ($contents === false) $this->invalid('chatbot', 'The selected import file could not be read.');

        try {
            $rows = ChatbotImportParser::parse($contents, $format);
        } catch (InvalidArgumentException $error) {
            $this->invalid('chatbot', $error->getMessage());
        }
        if ($rows === []) $this->invalid('chatbot', 'The import file does not contain any records.');

        $_SESSION['chatbot_import_preview'] = [
            'token' => bin2hex(random_bytes(24)),
            'created_at' => time(),
            'name' => basename((string) ($file['name'] ?? 'knowledge import')),
            'rows' => $rows,
        ];
        $_SESSION['flash'] = 'Import preview ready. No records have been changed yet.';
        $this->redirect('/admin/chatbot');
    }

    private function commitChatbotImport(array $admin): void
    {
        $preview = $_SESSION['chatbot_import_preview'] ?? null;
        $token = (string) ($_POST['preview_token'] ?? '');
        if (
            !is_array($preview)
            || !isset($preview['token'], $preview['created_at'], $preview['rows'])
            || (int) $preview['created_at'] < time() - 900
            || !hash_equals((string) $preview['token'], $token)
        ) {
            unset($_SESSION['chatbot_import_preview']);
            $this->invalid('chatbot', 'The import preview expired. Upload the file again to preview it.');
        }
        $mode = (string) ($_POST['duplicate_mode'] ?? 'skip');
        if (!in_array($mode, ['skip', 'update'], true)) $this->invalid('chatbot', 'Choose how duplicate records should be handled.');
        $rows = array_values(array_filter($preview['rows'], static fn (array $row): bool => $row['errors'] === []));
        if ($rows === []) $this->invalid('chatbot', 'There are no valid records to import.');

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $this->db->beginTransaction();
        try {
            $lookup = $this->db->prepare('SELECT id FROM chatbot_knowledge WHERE stable_key=?');
            $insert = $this->db->prepare(
                'INSERT INTO chatbot_knowledge (stable_key,question,answer,category,keywords,link_label,link_path,status,sort_order,source_type,created_by,created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())'
            );
            $update = $this->db->prepare(
                'UPDATE chatbot_knowledge SET question=?,answer=?,category=?,keywords=?,link_label=?,link_path=?,status=?,sort_order=?,source_type=\'import\',updated_at=NOW() WHERE id=?'
            );
            foreach ($rows as $row) {
                $data = $row['data'];
                $lookup->execute([$data['stable_key']]);
                $existingId = $lookup->fetchColumn();
                if ($existingId) {
                    if ($mode === 'skip') {
                        $skipped++;
                        continue;
                    }
                    $update->execute([
                        $data['question'], $data['answer'], $data['category'], $data['keywords'] ?: null,
                        $data['link_label'] ?: null, $data['link_path'] ?: null, $data['status'],
                        $data['sort_order'], (int) $existingId,
                    ]);
                    $updated++;
                    continue;
                }
                $insert->execute([
                    $data['stable_key'], $data['question'], $data['answer'], $data['category'],
                    $data['keywords'] ?: null, $data['link_label'] ?: null, $data['link_path'] ?: null,
                    $data['status'], $data['sort_order'], 'import', (int) $admin['id'],
                ]);
                $inserted++;
            }
            $details = json_encode([
                'filename' => $preview['name'],
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'invalid_rows' => count($preview['rows']) - count($rows),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $audit = $this->db->prepare(
                'INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address,details,created_at) VALUES (?,?,?,?,?,?,NOW())'
            );
            $audit->execute([(int) $admin['id'], 'imported', 'chatbot_knowledge', null, $_SERVER['REMOTE_ADDR'] ?? null, $details]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
        unset($_SESSION['chatbot_import_preview']);
        $_SESSION['flash'] = "Import complete: {$inserted} created, {$updated} updated, {$skipped} skipped.";
        $this->redirect('/admin/chatbot');
    }

    private function saveService(array $admin): void
    {
        $id = $this->postId('id', 'services');
        if ($id !== null) $this->assertRecordExists('services', $id, 'services');
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = $this->slug((string) ($_POST['slug'] ?? ''), $name);
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $icon = trim((string) ($_POST['icon'] ?? 'sparkles'));
        $sortOrder = filter_var($_POST['sort_order'] ?? '0', FILTER_VALIDATE_INT);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || strlen($name) > 150 || !$this->validSlug($slug) || strlen($slug) > 190 || !$this->uniqueSlug('services', $slug, $id)) {
            $this->invalid('services', 'Enter a service name and unique, valid URL slug.');
        }
        if (!preg_match('/^[a-z0-9-]{1,80}$/i', $icon) || $sortOrder === false || strlen($summary) > 5000 || strlen($description) > 20000) {
            $this->invalid('services', 'Check the service icon, order, summary, and description.');
        }
        $this->auditedChange((int) $admin['id'], $id === null ? 'created' : 'updated', 'service', function () use ($id, $name, $slug, $summary, $description, $icon, $sortOrder, $active): int {
            if ($id === null) {
                $stmt = $this->db->prepare('INSERT INTO services (name,slug,summary,description,icon,is_active,sort_order) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$name, $slug, $summary, $description, $icon, $active, $sortOrder]);
                return (int) $this->db->lastInsertId();
            }
            $stmt = $this->db->prepare('UPDATE services SET name=?,slug=?,summary=?,description=?,icon=?,is_active=?,sort_order=? WHERE id=?');
            $stmt->execute([$name, $slug, $summary, $description, $icon, $active, $sortOrder, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Service saved.';
        $this->redirect('/admin/services');
    }

    private function saveArticle(array $admin): void
    {
        $id = $this->postId('id', 'articles');
        if ($id !== null) $this->assertRecordExists('blog_posts', $id, 'articles');
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = $this->slug((string) ($_POST['slug'] ?? ''), $title);
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $cover = trim((string) ($_POST['cover_image'] ?? ''));
        $status = (string) ($_POST['status'] ?? 'draft');
        if ($title === '' || strlen($title) > 200 || !$this->validSlug($slug) || strlen($slug) > 220 || !$this->uniqueSlug('blog_posts', $slug, $id)) {
            $this->invalid('articles', 'Enter an article title and unique, valid URL slug.');
        }
        if (strlen($excerpt) > 5000 || strlen($content) > 100000 || !in_array($status, ['draft', 'published', 'archived'], true)) {
            $this->invalid('articles', 'Check the article content and publication status.');
        }
        if ($cover !== '' && !$this->validHttpUrl($cover)) $this->invalid('articles', 'Cover image must be a valid HTTP or HTTPS URL.');

        $this->auditedChange((int) $admin['id'], $id === null ? 'created' : 'updated', 'blog_post', function () use ($id, $title, $slug, $excerpt, $content, $cover, $status): int {
            if ($id === null) {
                $stmt = $this->db->prepare("INSERT INTO blog_posts (title,slug,excerpt,content,cover_image,status,published_at) VALUES (?,?,?,?,?,?,IF(?='published',NOW(),NULL))");
                $stmt->execute([$title, $slug, $excerpt, $content, $cover ?: null, $status, $status]);
                return (int) $this->db->lastInsertId();
            }
            $stmt = $this->db->prepare(
                "UPDATE blog_posts SET title=?,slug=?,excerpt=?,content=?,cover_image=?,status=?,
                 published_at=CASE WHEN ?='published' THEN COALESCE(published_at,NOW()) ELSE published_at END WHERE id=?"
            );
            $stmt->execute([$title, $slug, $excerpt, $content, $cover ?: null, $status, $status, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Article saved.';
        $this->redirect('/admin/articles');
    }

    private function saveNewsletter(array $admin): void
    {
        $id = $this->postId('id', 'newsletter');
        if ($id !== null) $this->assertRecordExists('newsletter_subscribers', $id, 'newsletter');
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $status = (string) ($_POST['status'] ?? 'active');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || !in_array($status, ['active', 'unsubscribed'], true)) {
            $this->invalid('newsletter', 'Enter a valid email address and subscription status.');
        }
        $duplicate = $this->db->prepare('SELECT id FROM newsletter_subscribers WHERE email=? AND id<>?');
        $duplicate->execute([$email, $id ?? 0]);
        if ($duplicate->fetch()) $this->invalid('newsletter', 'That email is already in the newsletter list.');

        $this->auditedChange((int) $admin['id'], $id === null ? 'created' : 'updated', 'newsletter_subscriber', function () use ($id, $email, $status): int {
            if ($id === null) {
                $stmt = $this->db->prepare('INSERT INTO newsletter_subscribers (email,status,subscribed_at) VALUES (?,?,NOW())');
                $stmt->execute([$email, $status]);
                return (int) $this->db->lastInsertId();
            }
            $stmt = $this->db->prepare('UPDATE newsletter_subscribers SET email=?,status=? WHERE id=?');
            $stmt->execute([$email, $status, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Newsletter subscription saved.';
        $this->redirect('/admin/newsletter');
    }

    private function changeStatus(array $admin, string $entity): void
    {
        $id = $this->requiredPostId('id', $entity);
        $status = (string) ($_POST['status'] ?? '');
        $statusMap = [
            'inquiries' => ['new', 'in_progress', 'responded', 'closed'],
            'newsletter' => ['active', 'unsubscribed'],
        ];
        if (!in_array($status, $statusMap[$entity] ?? [], true)) {
            $this->invalid($entity, 'Choose a valid status.');
        }
        $table = $entity === 'inquiries' ? 'service_inquiries' : 'newsletter_subscribers';
        $this->assertRecordExists($table, $id, $entity);
        $this->auditedChange((int) $admin['id'], 'status_changed', $entity === 'inquiries' ? 'service_inquiry' : 'newsletter_subscriber', function () use ($table, $id, $status): int {
            $stmt = $this->db->prepare("UPDATE {$table} SET status=? WHERE id=?");
            $stmt->execute([$status, $id]);
            return $id;
        });
        $_SESSION['flash'] = 'Record status updated.';
        $this->redirect('/admin/' . $entity);
    }

    private function postId(string $field, string $section): ?int
    {
        $raw = trim((string) ($_POST[$field] ?? ''));
        if ($raw === '' || $raw === '0') return null;
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) $this->invalid($section, 'The selected record identifier is invalid.');
        return (int) $id;
    }

    private function requiredPostId(string $field, string $section): int
    {
        $id = $this->postId($field, $section);
        if ($id === null) $this->invalid($section, 'Select a record to update.');
        return $id;
    }

    private function assertRecordExists(string $table, int $id, string $section, string $condition = ''): void
    {
        $allowedTables = ['products', 'product_categories', 'vendors', 'users', 'services', 'blog_posts', 'newsletter_subscribers', 'service_inquiries', 'chatbot_knowledge'];
        if (!in_array($table, $allowedTables, true)) throw new \LogicException('Unsupported record table.');
        $sql = "SELECT id FROM {$table} WHERE id=?";
        if ($condition !== '') $sql .= ' AND ' . $condition;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) $this->invalid($section, 'The selected record no longer exists.');
    }

    private function assertCatalogReference(string $table, ?int $id, string $section, string $message): void
    {
        if ($id === null) return;
        if (!in_array($table, ['product_categories', 'vendors'], true)) throw new \LogicException('Unsupported catalog reference.');
        $stmt = $this->db->prepare("SELECT id FROM {$table} WHERE id=?");
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) $this->invalid($section, $message);
    }

    private function uniqueSlug(string $table, string $slug, ?int $id): bool
    {
        $allowedTables = ['products', 'product_categories', 'services', 'blog_posts'];
        if (!in_array($table, $allowedTables, true)) throw new \LogicException('Unsupported slug table.');
        $sql = "SELECT id FROM {$table} WHERE slug=?";
        $params = [$slug];
        if ($id !== null) {
            $sql .= ' AND id<>?';
            $params[] = $id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return !$stmt->fetchColumn();
    }

    private function slug(string $value, string $fallback): string
    {
        $value = trim($value) !== '' ? $value : $fallback;
        return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    }

    private function validSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

    private function validHttpUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function validChatbotPath(string $path): bool
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

    private function auditedChange(int $adminId, string $action, string $entity, callable $operation): int
    {
        $this->db->beginTransaction();
        try {
            $entityId = $operation();
            $stmt = $this->db->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,ip_address,created_at) VALUES (?,?,?,?,?,NOW())');
            $stmt->execute([$adminId, $action, $entity, $entityId, $_SERVER['REMOTE_ADDR'] ?? null]);
            $this->db->commit();
            return $entityId;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function invalid(string $section, string $message): never
    {
        $_SESSION['flash_error'] = $message;
        $this->redirect('/admin/' . $section);
    }
}
