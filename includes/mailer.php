<?php
require_once __DIR__ . '/functions.php';

function send_via_brevo(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    if (!defined('BREVO_API_KEY') || trim((string)BREVO_API_KEY) === '') {
        return false;
    }

    $payload = [
        'sender' => [
            'name' => MAIL_FROM_NAME,
            'email' => MAIL_FROM_EMAIL,
        ],
        'to' => [
            ['email' => $to],
        ],
        'subject' => $subject,
        'htmlContent' => $html,
    ];

    if ($replyTo) {
        $payload['replyTo'] = [
            'email' => $replyTo,
        ];
    }

    $ch = curl_init(BREVO_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('Brevo mailer curl error: ' . $curlError);
        return false;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('Brevo mailer failed [' . $httpCode . ']: ' . (string)$response);
        return false;
    }

    return true;
}

function send_via_php_mail(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>';
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $sent = @mail($to, $subject, $html, implode("
", $headers));
    return (bool)$sent;
}

function send_basic_mail(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    if (send_via_brevo($to, $subject, $html, $replyTo)) {
        return true;
    }
    return send_via_php_mail($to, $subject, $html, $replyTo);
}
