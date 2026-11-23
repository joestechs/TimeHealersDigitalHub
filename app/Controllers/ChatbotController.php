<?php
namespace App\Controllers;

use App\Services\ChatbotProviderException;
use App\Services\ChatbotService;
use PDO;

final class ChatbotController extends BaseController
{
    private ChatbotService $chatbot;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->chatbot = new ChatbotService($db);
    }

    public function message(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
            $this->json(['error' => 'Your session expired. Refresh the page and try again.'], 403);
            return;
        }

        $settings = $this->chatbot->settings();
        $supportAction = ['label' => 'Contact our team', 'url' => appUrl('/contact')];
        if (!(bool) $settings['is_enabled']) {
            $this->json([
                'error' => 'Chat support is currently unavailable. Please contact our team.',
                'action' => $supportAction,
            ], 503);
            return;
        }

        $message = trim((string) ($_POST['message'] ?? ''));
        if ($message === '' || !mb_check_encoding($message, 'UTF-8') || mb_strlen($message, 'UTF-8') > 1000) {
            $this->json(['error' => 'Enter a message of up to 1,000 characters.'], 422);
            return;
        }

        $externalProcessing = $this->chatbot->externalProcessingConsentRequired();
        if ($externalProcessing && (string) ($_POST['consent'] ?? '') !== '1') {
            $this->json(['error' => 'Please accept the external-provider notice before sending a message.'], 428);
            return;
        }

        $now = time();
        $timestamps = $_SESSION['chatbot_rate_limit'] ?? [];
        if (!is_array($timestamps)) $timestamps = [];
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 60
        ));
        if (count($timestamps) >= 8) {
            $retryAfter = max(1, 60 - ($now - $timestamps[0]));
            header('Retry-After: ' . $retryAfter);
            $this->json(['error' => 'You have sent several messages recently. Please wait a moment and try again.'], 429);
            return;
        }
        $timestamps[] = $now;
        $_SESSION['chatbot_rate_limit'] = $timestamps;
        session_write_close();

        try {
            $this->json($this->chatbot->respond($message), 200);
        } catch (ChatbotProviderException $error) {
            error_log('Chatbot provider request failed: ' . $error->getMessage());
            $this->json([
                'error' => 'The configured AI provider is temporarily unavailable. ' . (string) $settings['fallback_message'],
                'action' => $supportAction,
            ], 502);
        }
    }

    private function json(array $payload, int $status): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
