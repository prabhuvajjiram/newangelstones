<?php
require_once 'config.php';

class GmailMailer {
    private $pdo;
    private $sender_email;
    private $access_token;
    private $refresh_token;

    public function __construct($sender_email) {
        global $pdo;
        $this->pdo = $pdo;
        $this->sender_email = $sender_email;
        $this->loadTokens();
    }

    private function loadTokens() {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM email_settings WHERE setting_name IN ('access_token', 'refresh_token')");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $this->access_token = $results['access_token'] ?? null;
        $this->refresh_token = $results['refresh_token'] ?? null;
    }

    private function refreshAccessToken() {
        $client_id = GOOGLE_CLIENT_ID;
        $client_secret = GOOGLE_CLIENT_SECRET;
        $refresh_token = $this->refresh_token;

        $url = 'https://oauth2.googleapis.com/token';
        $data = [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception('Failed to refresh access token');
        }

        $response = json_decode($result, true);
        $this->access_token = $response['access_token'];

        // Update the access token in database
        $stmt = $this->pdo->prepare("UPDATE email_settings SET setting_value = ? WHERE setting_name = 'access_token'");
        $stmt->execute([$this->access_token]);
    }

    public function sendEmail($to, $subject, $body, $attachments = []) {
        if (!$this->access_token) {
            throw new Exception('Access token not available');
        }

        // Create email in base64 format
        $boundary = uniqid(rand(), true);
        $email = "";
        $email .= "From: {$this->sender_email}\r\n";
        $email .= "To: {$to}\r\n";
        $email .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        
        if (!empty($attachments)) {
            $email .= "Content-Type: multipart/mixed; boundary={$boundary}\r\n\r\n";
            $email .= "--{$boundary}\r\n";
            $email .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $email .= $body . "\r\n\r\n";

            foreach ($attachments as $attachment) {
                $content = file_get_contents($attachment['path']);
                $encoded_content = base64_encode($content);
                
                $email .= "--{$boundary}\r\n";
                $email .= "Content-Type: {$attachment['type']}\r\n";
                $email .= "Content-Transfer-Encoding: base64\r\n";
                $email .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n\r\n";
                $email .= $encoded_content . "\r\n\r\n";
            }
            $email .= "--{$boundary}--";
        } else {
            $email .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $email .= $body;
        }

        $encoded_email = base64_encode($email);
        $encoded_email = str_replace(['+', '/', '='], ['-', '_', ''], $encoded_email);

        $url = 'https://www.googleapis.com/gmail/v1/users/me/messages/send';
        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ];

        $data = json_encode(['raw' => $encoded_email]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 401) {
            // Token expired, refresh and try again
            $this->refreshAccessToken();
            return $this->sendEmail($to, $subject, $body, $attachments);
        }

        if ($http_code !== 200) {
            throw new Exception('Failed to send email: ' . $response);
        }

        return json_decode($response, true);
    }

    public function queueEmail($to, $subject, $body, $user_id, $customer_id = null, $scheduled_for = null, $attachments = []) {
        try {
            $this->pdo->beginTransaction();

            // Insert into email queue
            $stmt = $this->pdo->prepare("
                INSERT INTO email_queue 
                (to_email, subject, body, customer_id, user_id, scheduled_for) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$to, $subject, $body, $customer_id, $user_id, $scheduled_for]);
            $email_id = $this->pdo->lastInsertId();

            // Handle attachments if any
            if (!empty($attachments)) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO email_attachments 
                    (email_id, file_name, file_path, file_size, mime_type) 
                    VALUES (?, ?, ?, ?, ?)
                ");

                foreach ($attachments as $attachment) {
                    $stmt->execute([
                        $email_id,
                        $attachment['name'],
                        $attachment['path'],
                        $attachment['size'],
                        $attachment['type']
                    ]);
                }
            }

            // Log the queued event
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs 
                (email_id, event_type, event_data) 
                VALUES (?, 'queued', ?)
            ");
            $stmt->execute([$email_id, json_encode(['queued_at' => date('Y-m-d H:i:s')])]);

            $this->pdo->commit();
            return $email_id;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function processEmailQueue() {
        // Get pending emails that are scheduled for now or in the past
        $stmt = $this->pdo->prepare("
            SELECT eq.*, GROUP_CONCAT(ea.file_path) as attachment_paths 
            FROM email_queue eq 
            LEFT JOIN email_attachments ea ON eq.id = ea.email_id 
            WHERE eq.status = 'pending' 
            AND (eq.scheduled_for IS NULL OR eq.scheduled_for <= NOW())
            GROUP BY eq.id 
            LIMIT 50
        ");
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($emails as $email) {
            try {
                $attachments = [];
                if ($email['attachment_paths']) {
                    $paths = explode(',', $email['attachment_paths']);
                    foreach ($paths as $path) {
                        $attachments[] = [
                            'path' => $path,
                            'name' => basename($path),
                            'type' => mime_content_type($path),
                            'size' => filesize($path)
                        ];
                    }
                }

                $this->sendEmail(
                    $email['to_email'],
                    $email['subject'],
                    $email['body'],
                    $attachments
                );

                // Update email status to sent
                $stmt = $this->pdo->prepare("
                    UPDATE email_queue 
                    SET status = 'sent', sent_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$email['id']]);

                // Log the sent event
                $stmt = $this->pdo->prepare("
                    INSERT INTO email_logs 
                    (email_id, event_type, event_data) 
                    VALUES (?, 'sent', ?)
                ");
                $stmt->execute([$email['id'], json_encode(['sent_at' => date('Y-m-d H:i:s')])]);

            } catch (Exception $e) {
                // Update email status to failed
                $stmt = $this->pdo->prepare("
                    UPDATE email_queue 
                    SET status = 'failed', error_message = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$e->getMessage(), $email['id']]);

                // Log the failure
                $stmt = $this->pdo->prepare("
                    INSERT INTO email_logs 
                    (email_id, event_type, event_data) 
                    VALUES (?, 'failed', ?)
                ");
                $stmt->execute([$email['id'], json_encode([
                    'error' => $e->getMessage(),
                    'failed_at' => date('Y-m-d H:i:s')
                ])]);
            }
        }
    }
}

// Function to process email queue
function processEmailQueue() {
    global $pdo;
    
    // Get pending emails that are scheduled for now or in the past
    $stmt = $pdo->prepare("
        SELECT eq.*, GROUP_CONCAT(ea.file_path) as attachment_paths 
        FROM email_queue eq 
        LEFT JOIN email_attachments ea ON eq.id = ea.email_id 
        WHERE eq.status = 'pending' 
        AND (eq.scheduled_for IS NULL OR eq.scheduled_for <= NOW())
        GROUP BY eq.id 
        LIMIT 50
    ");
    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mailer = new GmailMailer(GMAIL_SENDER_EMAIL);

    foreach ($emails as $email) {
        try {
            $attachments = [];
            if ($email['attachment_paths']) {
                $paths = explode(',', $email['attachment_paths']);
                foreach ($paths as $path) {
                    $attachments[] = [
                        'path' => $path,
                        'name' => basename($path),
                        'type' => mime_content_type($path),
                        'size' => filesize($path)
                    ];
                }
            }

            $mailer->sendEmail(
                $email['to_email'],
                $email['subject'],
                $email['body'],
                $attachments
            );

            // Update email status to sent
            $stmt = $pdo->prepare("
                UPDATE email_queue 
                SET status = 'sent', sent_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$email['id']]);

            // Log the sent event
            $stmt = $pdo->prepare("
                INSERT INTO email_logs 
                (email_id, event_type, event_data) 
                VALUES (?, 'sent', ?)
            ");
            $stmt->execute([$email['id'], json_encode(['sent_at' => date('Y-m-d H:i:s')])]);

        } catch (Exception $e) {
            // Update email status to failed
            $stmt = $pdo->prepare("
                UPDATE email_queue 
                SET status = 'failed', error_message = ? 
                WHERE id = ?
            ");
            $stmt->execute([$e->getMessage(), $email['id']]);

            // Log the failure
            $stmt = $pdo->prepare("
                INSERT INTO email_logs 
                (email_id, event_type, event_data) 
                VALUES (?, 'failed', ?)
            ");
            $stmt->execute([$email['id'], json_encode([
                'error' => $e->getMessage(),
                'failed_at' => date('Y-m-d H:i:s')
            ])]);
        }
    }
}
