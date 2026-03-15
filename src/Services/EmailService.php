<?php

namespace AnimaID\Services;

use AnimaID\Config\ConfigManager;
use Monolog\Logger;

/**
 * Email Service
 * Sends transactional emails using PHP's built-in mail() function.
 * All sending is gated by the features.email_notifications config flag.
 */
class EmailService
{
    private ConfigManager $config;
    private Logger $logger;

    public function __construct(ConfigManager $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    private function isEnabled(): bool
    {
        return $this->config->get('features.email_notifications') === true;
    }

    /**
     * Build RFC-compliant headers and send an email via mail().
     */
    private function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $fromAddress = $this->config->get('email.from_address', 'noreply@animaid.local');
        $fromName    = $this->config->get('email.from_name', 'AnimaID System');

        // Build a multipart/alternative message so clients can display text or HTML.
        $boundary = '==boundary_' . bin2hex(random_bytes(8));

        if (empty($textBody)) {
            // Strip HTML tags to produce a plain-text fallback.
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));
        }

        $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromAddress}>\r\n";
        $headers .= "Reply-To: {$fromAddress}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: AnimaID/PHP\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($textBody)) . "\r\n";

        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        $body .= "--{$boundary}--";

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $result = mail($to, $encodedSubject, $body, $headers);

        if ($result) {
            $this->logger->info('Email sent', ['to' => $to, 'subject' => $subject]);
        } else {
            $this->logger->error('Email send failed', ['to' => $to, 'subject' => $subject]);
        }

        return $result;
    }

    /**
     * Send a welcome email to a newly registered user.
     */
    public function sendWelcome(string $email, string $username): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $appName = $this->config->get('system.name', 'AnimaID');
        $subject = "Welcome to {$appName}, {$username}!";

        $html = "<!DOCTYPE html><html><body>"
            . "<h2>Welcome to {$appName}!</h2>"
            . "<p>Hello <strong>" . htmlspecialchars($username) . "</strong>,</p>"
            . "<p>Your account has been created successfully. You can now log in and start using {$appName}.</p>"
            . "<p>If you did not create this account, please ignore this email or contact support.</p>"
            . "<p>Best regards,<br>The {$appName} Team</p>"
            . "</body></html>";

        return $this->send($email, $subject, $html);
    }

    /**
     * Send a password-reset link email.
     */
    public function sendPasswordReset(string $email, string $username, string $token): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $appName = $this->config->get('system.name', 'AnimaID');
        $subject = "Password Reset Request – {$appName}";

        $html = "<!DOCTYPE html><html><body>"
            . "<h2>Password Reset</h2>"
            . "<p>Hello <strong>" . htmlspecialchars($username) . "</strong>,</p>"
            . "<p>We received a request to reset your password. Use the token below to complete the process:</p>"
            . "<p style=\"font-family:monospace;font-size:1.2em;background:#f3f4f6;padding:8px 16px;display:inline-block;border-radius:4px;\">"
            . htmlspecialchars($token)
            . "</p>"
            . "<p>This token will expire shortly. If you did not request a password reset, you can safely ignore this email.</p>"
            . "<p>Best regards,<br>The {$appName} Team</p>"
            . "</body></html>";

        return $this->send($email, $subject, $html);
    }

    /**
     * Notify the user that their password has been changed.
     */
    public function sendPasswordChanged(string $email, string $username): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $appName = $this->config->get('system.name', 'AnimaID');
        $subject = "Your {$appName} password has been changed";

        $html = "<!DOCTYPE html><html><body>"
            . "<h2>Password Changed</h2>"
            . "<p>Hello <strong>" . htmlspecialchars($username) . "</strong>,</p>"
            . "<p>Your password was successfully changed. If you did not make this change, please contact support immediately.</p>"
            . "<p>Best regards,<br>The {$appName} Team</p>"
            . "</body></html>";

        return $this->send($email, $subject, $html);
    }

    /**
     * Send an attendance reminder for an upcoming event.
     */
    public function sendAttendanceReminder(
        string $email,
        string $name,
        string $eventName,
        string $eventDate
    ): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        $appName = $this->config->get('system.name', 'AnimaID');
        $subject = "Reminder: {$eventName} on {$eventDate}";

        $html = "<!DOCTYPE html><html><body>"
            . "<h2>Attendance Reminder</h2>"
            . "<p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>"
            . "<p>This is a friendly reminder that <strong>" . htmlspecialchars($eventName) . "</strong> is scheduled for <strong>" . htmlspecialchars($eventDate) . "</strong>.</p>"
            . "<p>Please make sure to attend or notify us if you are unable to participate.</p>"
            . "<p>Best regards,<br>The {$appName} Team</p>"
            . "</body></html>";

        return $this->send($email, $subject, $html);
    }
}
