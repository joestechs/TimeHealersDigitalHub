<?php
namespace App\Controllers;

class AccountController extends BaseController
{
    private function renderPage(string $title, string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        require dirname(__DIR__, 2) . '/resources/views/pages/' . $view . '.php';
        $pageContent = ob_get_clean();
        $this->render('layouts/page', compact('title', 'pageContent'));
    }

    private function currentUser(): array
    {
        $userId = filter_var($_SESSION['user']['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId) {
            $this->redirect('/login');
        }

        $stmt = $this->db->prepare("SELECT id,name,email,role,status FROM users WHERE id=? AND status='active' AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            unset($_SESSION['user']);
            $_SESSION['flash_error'] = 'Your account is unavailable. Please sign in or contact the site administrator.';
            $this->redirect('/login');
        }

        $_SESSION['user'] = $user;
        return $user;
    }

    public function loginPage(): void
    {
        $this->renderPage('Sign In', 'login');
    }

    public function registerPage(): void
    {
        $this->renderPage('Create Account', 'register');
    }

    public function login(): void
    {
        $this->requireCsrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email=? AND status='active' AND deleted_at IS NULL");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid email or password.';
            $this->redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
        $this->redirect(in_array($user['role'], ['super_admin', 'administrator'], true) ? '/admin' : '/account');
    }

    public function register(): void
    {
        $this->requireCsrf();
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || strlen($password) < 12) {
            $_SESSION['flash_error'] = 'Use a valid email and a password with at least 12 characters.';
            $this->redirect('/register');
        }

        $exists = $this->db->prepare("SELECT id FROM users WHERE email=?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $_SESSION['flash_error'] = 'An account already exists for that email.';
            $this->redirect('/register');
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->db->prepare("INSERT INTO users (name,email,password_hash,role,status,created_at) VALUES (?,?,?,'customer','active',NOW())");
        $stmt->execute([$name, $email, $hash]);
        $_SESSION['flash'] = 'Account created. Please sign in.';
        $this->redirect('/login');
    }

    public function updateProfile(): void
    {
        $user = $this->currentUser();
        $this->requireCsrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($name === '' || strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $_SESSION['flash_error'] = 'Enter your name and a valid email address.';
            $this->redirect('/account/settings');
        }

        $exists = $this->db->prepare('SELECT id FROM users WHERE email=? AND id<>?');
        $exists->execute([$email, $user['id']]);
        if ($exists->fetch()) {
            $_SESSION['flash_error'] = 'That email address is already associated with another account.';
            $this->redirect('/account/settings');
        }

        $stmt = $this->db->prepare('UPDATE users SET name=?,email=?,updated_at=NOW() WHERE id=?');
        $stmt->execute([$name, $email, $user['id']]);
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['flash'] = 'Your profile has been updated.';
        $this->redirect('/account/settings');
    }

    public function updatePassword(): void
    {
        $user = $this->currentUser();
        $this->requireCsrf();
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id=?');
        $stmt->execute([$user['id']]);
        $passwordHash = $stmt->fetchColumn();
        if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
            $_SESSION['flash_error'] = 'Your current password was not correct.';
            $this->redirect('/account/settings');
        }
        if (strlen($newPassword) < 12 || !hash_equals($newPassword, $confirmation)) {
            $_SESSION['flash_error'] = 'Use a matching new password of at least 12 characters.';
            $this->redirect('/account/settings');
        }

        $stmt = $this->db->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?');
        $stmt->execute([password_hash($newPassword, PASSWORD_ARGON2ID), $user['id']]);
        session_regenerate_id(true);
        $_SESSION['flash'] = 'Your password has been changed.';
        $this->redirect('/account/settings');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        session_unset();
        session_destroy();
        $this->redirect('/');
    }

    public function dashboard(string $path): void
    {
        $user = $this->currentUser();
        if ($path === '/account/settings') {
            $this->renderPage('Account Settings', 'account-settings', compact('user'));
            return;
        }
        if ($path === '/account/orders') {
            $stmt = $this->db->prepare(
                'SELECT id,order_type,status,subtotal,currency,created_at
                 FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 100'
            );
            $stmt->execute([$user['id']]);
            $orders = $stmt->fetchAll();
            $this->renderPage('My Orders', 'account-orders', compact('user', 'orders'));
            return;
        }
        if ($path === '/account/downloads') {
            $this->renderPage('My Downloads', 'account-downloads', compact('user'));
            return;
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
        $stmt->execute([$user['id']]);
        $orderCount = (int) $stmt->fetchColumn();
        $this->renderPage('My Account', 'account', compact('user', 'orderCount'));
    }
}
