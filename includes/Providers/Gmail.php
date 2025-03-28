<?php

namespace FreeMailSMTP\Providers;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;

class Gmail extends BaseProvider
{
    public function get_api_url()
    {
        return;
    }

    public function get_headers()
    {
        return [];
    }

    private $client;
    private $service;

    public function __construct($config_keys)
    {
        parent::__construct($config_keys);
        try {
            $this->client = new Google_Client();
            $this->client->setClientId($this->config_keys['client_id']);
            $this->client->setClientSecret($this->config_keys['client_secret']);
            $this->client->setRedirectUri(admin_url('admin.php?page=free_mail_smtp-providers'));
            $this->client->setAccessType('offline');
            $this->client->setApprovalPrompt('force');
            $this->client->addScope(Google_Service_Gmail::GMAIL_SEND);
            $this->client->addScope(Google_Service_Gmail::GMAIL_READONLY);
            $this->client->addScope(Google_Service_Gmail::GMAIL_LABELS);

            $this->validateAccessToken();
            $this->service = new Google_Service_Gmail($this->client);
        } catch (\Exception $e) {
            throw new \Exception('Failed to initialize Gmail provider: ' . esc_html($e->getMessage()));
        }
    }

    private function save_access_token($token)
    {
        update_option('free_mail_smtp_gmail_access_token', $token);
    }

    private function save_refresh_token($token)
    {
        update_option('free_mail_smtp_gmail_refresh_token', $token);
    }

    private function get_access_token()
    {
        return get_option('free_mail_smtp_gmail_access_token');
    }

    private function get_refresh_token()
    {
        return get_option('free_mail_smtp_gmail_refresh_token');
    }

    public function send($data)
    {
        $this->validateAccessToken();

        try {
            // Create the email message
            $boundary = uniqid(wp_rand(), true);
            $email_parts = [];
            $email_from = $this->config_keys['email_from_overwrite'] ? $this->config_keys['email_from_overwrite'] : $data['from_email'];

            // Add headers
            $email_parts[] = "To: {$data['to'][0]}";
            $email_parts[] = "From: {$data['from_name']} <{$email_from}>";
            $email_parts[] = "Subject: {$data['subject']}";
            $email_parts[] = "MIME-Version: 1.0";
            $email_parts[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
            $email_parts[] = "";

            // Add HTML content
            $email_parts[] = "--{$boundary}";
            $email_parts[] = "Content-Type: text/html; charset=UTF-8";
            $email_parts[] = "Content-Transfer-Encoding: base64";
            $email_parts[] = "";
            $email_parts[] = base64_encode($data['message']);

            // Add attachments if any
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    $email_parts[] = "--{$boundary}";
                    $email_parts[] = "Content-Type: {$attachment['type']}; name=\"{$attachment['filename']}\"";
                    $email_parts[] = "Content-Disposition: attachment; filename=\"{$attachment['filename']}\"";
                    $email_parts[] = "Content-Transfer-Encoding: base64";
                    $email_parts[] = "";
                    // Ensure content is base64 encoded
                    $email_parts[] = base64_encode($attachment['content']);
                }
            }

            $email_parts[] = "--{$boundary}--";

            // Create the message
            $email_content = implode("\n", $email_parts);
            $message = new Google_Service_Gmail_Message();
            $message->setRaw(base64_encode($email_content));

            // Send the message
            $result = $this->service->users_messages->send('me', $message);

            return [
                'message_id' => $result->getId(),
                'provider_response' => $result
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to send email via Gmail: ' . esc_html($e->getMessage()));
        }
    }

    public function test_connection()
    {
        try {
            $this->validateAccessToken();
            $this->service->users_labels->listUsersLabels('me');
            return [
                'success' => true,
                'message' => 'Gmail connection verified successfully.'
            ];
        } catch (\Exception $e) {
            throw new \Exception('Gmail connection test failed: ' . esc_html($e->getMessage()));
        }
    }

    public function get_auth_url()
    {
        $this->client->setState('gmail');
        return $this->client->createAuthUrl();
    }

    protected function get_error_message($body, $code)
    {
        $data = json_decode($body, true);

        if (isset($data['error']['message'])) {
            return "Gmail API error: {$data['error']['message']}. (HTTP $code)";
        }

        if (isset($data['message'])) {
            return "Gmail API error: {$data['message']}. (HTTP $code)";
        }

        return "Gmail API error (HTTP $code)";
    }

    public function get_analytics($filters = [])
    {
        try {
            $this->validateAccessToken();
            $per_page = isset($filters['per_page']) ? (int)$filters['per_page'] : 10;
            $messages = $this->service->users_messages->listUsersMessages('me', [
                'maxResults' => $per_page,
                'q' => "in:sent after:{$filters['date_from']} before:{$filters['date_to']}"
            ]);
            $analytics = [];
            foreach ($messages->getMessages() as $message) {
                $msg = $this->service->users_messages->get('me', $message->getId());
                $headers = $this->get_message_headers($msg);

                $analytics[] = [
                    'id' => $message->getId(),
                    'subject' => $headers['subject'] ?? '',
                    'to' => $headers['to'] ?? '',
                    'date' => $headers['date'] ?? '',
                    'status' => 'sent'
                ];
            }

            return [
                'data' => $analytics,
                'columns' => ['id', 'subject', 'to', 'date', 'status']
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to get Gmail analytics: ' . esc_html($e->getMessage()));
        }
    }

    private function get_message_headers($message)
    {
        $headers = [];
        foreach ($message->getPayload()->getHeaders() as $header) {
            $headers[strtolower($header->getName())] = $header->getValue();
        }
        return $headers;
    }


    public function handle_oauth_callback($code)
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            $this->save_access_token($token);
            
            if (!empty($token['refresh_token'])) {
                $this->save_refresh_token($token['refresh_token']);
            }
            $this->client->setAccessToken($token);
            $this->service = new Google_Service_Gmail($this->client);
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to set Gmail token: ' . esc_html($e->getMessage()));
        }
    }
    
    private function validateAccessToken()
    {
        $accessToken = $this->get_access_token();
        if (!empty($accessToken)) {
            $this->client->setAccessToken($accessToken);
            if ($this->client->isAccessTokenExpired()) {
                try {
                    $refreshToken = $this->get_refresh_token();
                    if (empty($refreshToken)) {
                        throw new \Exception('Refresh token is missing. Re-authorization required.');
                    }
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                    $this->save_access_token($this->client->getAccessToken());
                } catch (\Exception $e) {
                    throw new \Exception('Authentication expired. Please reconnect your Gmail account.');
                }
            }
        } else {
            throw new \Exception('Gmail authentication required. Please connect your account.');
        }
    }
}
