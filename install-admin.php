<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Administrator installation is CLI-only.\n");
}

require __DIR__ . '/bootstrap/database.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $value = fgets(STDIN);
    if ($value === false) {
        throw new RuntimeException('Unable to read input from the terminal.');
    }

    return trim($value);
}

function promptPassword(bool $confirmation = false): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('PowerShell password input is unavailable because shell_exec is disabled.');
        }

        $prompt = $confirmation ? 'Confirm password' : 'Password (12+ characters)';
        $command = <<<'POWERSHELL'
powershell.exe -NoProfile -Command "$secure = Read-Host '__PROMPT__' -AsSecureString; $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); try { [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes([Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer))) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }"
POWERSHELL;
        $command = str_replace('__PROMPT__', $prompt, $command);
        $output = shell_exec($command);
        $password = is_string($output) ? base64_decode(trim($output), true) : false;
        if (!is_string($password)) {
            throw new RuntimeException('Unable to read the hidden password from PowerShell.');
        }

        return $password;
    }

    fwrite(STDOUT, $confirmation ? 'Confirm password: ' : 'Password (12+ characters): ');
    exec('stty -echo', $output, $status);
    if ($status !== 0) {
        throw new RuntimeException('Hidden password input requires an interactive terminal.');
    }

    try {
        $password = fgets(STDIN);
    } finally {
        exec('stty echo', $output, $restoreStatus);
        fwrite(STDOUT, PHP_EOL);
        if ($restoreStatus !== 0) {
            throw new RuntimeException('Unable to restore terminal input after password entry.');
        }
    }

    if ($password === false) {
        throw new RuntimeException('Unable to read the password from the terminal.');
    }

    return rtrim($password, "\r\n");
}

$role = 'super_admin';
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--role=')) {
        $role = substr($argument, strlen('--role='));
    } else {
        fwrite(STDERR, "Usage: php install-admin.php [--role=super_admin|administrator]\n");
        exit(1);
    }
}

if (!in_array($role, ['super_admin', 'administrator'], true)) {
    fwrite(STDERR, "Role must be super_admin or administrator.\n");
    exit(1);
}

echo "Create a THDH {$role} account\n";
$name = prompt('Name: ');
$email = strtolower(prompt('Email: '));
$password = promptPassword();
$confirmation = promptPassword(true);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
    fwrite(STDERR, "Invalid account details. Use a valid email and a password of at least 12 characters.\n");
    exit(1);
}
if (!hash_equals($password, $confirmation)) {
    fwrite(STDERR, "The passwords do not match.\n");
    exit(1);
}

if ($role === 'administrator') {
    $superAdminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='super_admin' AND status='active'")->fetchColumn();
    if ($superAdminCount === 0) {
        fwrite(STDERR, "Create an active super_admin account before creating an administrator.\n");
        exit(1);
    }
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    fwrite(STDERR, "An account already exists for that email.\n");
    exit(1);
}

$stmt = $pdo->prepare("INSERT INTO users (name,email,password_hash,role,status,email_verified_at,created_at) VALUES (?,?,?,?,?,NOW(),NOW())");
$stmt->execute([$name, $email, password_hash($password, PASSWORD_ARGON2ID), $role, 'active']);
echo ucfirst(str_replace('_', ' ', $role)) . " account created securely.\n";
