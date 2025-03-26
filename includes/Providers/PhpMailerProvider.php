<?php
namespace FreeMailSMTP\Providers;

// Use WordPress's version of PHPMailer if not already loaded.
if ( ! class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class PhpMailerProvider { // Assuming you implement ProviderInterface
    /**
     * Send email using PHPMailer fallback without requiring SMTP credentials.
     *
     * @param array $email_data Associative array containing to, subject, body, from_email, from_name, headers, etc.
     * @return array Response data (e.g., message_id)
     * @throws \Exception If sending fails
     */
    public function send($email_data) {
        // Get message content from either 'body' or 'message' key
        $message = !empty($email_data['body']) ? $email_data['body'] : 
                   (!empty($email_data['message']) ? $email_data['message'] : '');

        if (empty($message)) {
            throw new \Exception('Message body cannot be empty');
        }

        if (empty($email_data['to'])) {
            throw new \Exception('Recipient(s) cannot be empty');
        }

        $mail = new PHPMailer(true);
        try {
            // Use PHP's mail() function by switching to mail transport
            $mail->isMail();
            
            $mail->setFrom($email_data['from_email'] ?? 'default@example.com', $email_data['from_name'] ?? 'Default');
            
            // Ensure recipients is an array
            $recipients = isset($email_data['to']) ? (array) $email_data['to'] : [];
            foreach ($recipients as $recipient) {
                $mail->addAddress($recipient);
            }

            $mail->Subject = !empty($email_data['subject']) ? $email_data['subject'] : '(no subject)';
            $mail->Body    = $message;
            
            // Check if content is HTML
            $is_html = isset($email_data['headers']) && 
                       (strpos($email_data['headers'], 'text/html') !== false ||
                        strpos($email_data['headers'], 'content-type: html') !== false);
            $mail->isHTML($is_html);

            // Set plain text version if HTML
            if ($is_html) {
                $mail->AltBody = wp_strip_all_tags($message);
            }

            // Optionally add custom headers, cc, bcc, and attachments...
            if (!empty($email_data['headers'])) {
                $headers = $this->parse_headers($email_data['headers']);
                foreach ($headers as $name => $value) {
                    $mail->addCustomHeader($name, $value);
                }
            }
            
            if (!$mail->send()) {
                throw new \Exception($mail->ErrorInfo);
            }

            return ['message_id' => $mail->getLastMessageID()];
        } catch (PHPMailerException $e) {
            throw new \Exception('PHPMailer error: ' . esc_html($e->getMessage()));
        }
    }

    /**
     * Parse headers string into an associative array.
     *
     * @param string|array $headers
     * @return array
     */
    private function parse_headers($headers) {
        $parsed = [];
        if (is_string($headers)) {
            $headers = explode("\n", str_replace("\r\n", "\n", $headers));
        }
        foreach ((array)$headers as $header) {
            if (strpos($header, ':') !== false) {
                list($name, $value) = explode(':', $header, 2);
                $parsed[trim($name)] = trim($value);
            }
        }
        return $parsed;
    }
}