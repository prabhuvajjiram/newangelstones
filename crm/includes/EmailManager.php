<?php
class EmailManager {
    private $pdo;
    private $googleClient;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeGoogleClient();
    }

    private function initializeGoogleClient() {
        $this->googleClient = new Google_Client();
        $this->googleClient->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->googleClient->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->googleClient->setRedirectUri($_ENV['APP_URL'] . '/gmail_callback.php');
        $this->googleClient->addScope('https://www.googleapis.com/auth/gmail.modify');
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('consent');
    }

    public function getEmailSettings($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_settings 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmailTemplates() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_templates 
            WHERE is_active = 1 
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmailStats() {
        // Get emails sent in last 30 days
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM email_tracking 
            WHERE status = 'sent' 
            AND sent_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $sent30Days = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Calculate average response time
        $stmt = $this->pdo->prepare("
            SELECT AVG(TIMESTAMPDIFF(HOUR, e1.sent_date, e2.sent_date)) as avg_hours
            FROM email_tracking e1
            JOIN email_tracking e2 ON e1.thread_id = e2.thread_id
            WHERE e1.status = 'sent' 
            AND e2.status = 'received'
            AND e2.sent_date > e1.sent_date
        ");
        $stmt->execute();
        $avgResponseTime = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_hours'] ?? 0, 1);

        // Calculate open rate
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'read' THEN 1 END) * 100.0 / COUNT(*) as open_rate
            FROM email_tracking 
            WHERE status IN ('sent', 'read')
            AND sent_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $openRate = round($stmt->fetch(PDO::FETCH_ASSOC)['open_rate'] ?? 0, 1);

        // Calculate response rate
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'replied' THEN 1 END) * 100.0 / COUNT(*) as response_rate
            FROM email_tracking 
            WHERE status IN ('sent', 'replied')
            AND sent_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $responseRate = round($stmt->fetch(PDO::FETCH_ASSOC)['response_rate'] ?? 0, 1);

        return [
            'sent_30_days' => $sent30Days,
            'avg_response_time' => $avgResponseTime,
            'open_rate' => $openRate,
            'response_rate' => $responseRate
        ];
    }

    public function getRecentActivity($limit = 10) {
        $stmt = $this->pdo->prepare("
            SELECT 
                et.*,
                CASE 
                    WHEN c.id IS NOT NULL THEN c.name 
                    WHEN comp.id IS NOT NULL THEN comp.name 
                    ELSE et.recipients 
                END as contact,
                CASE 
                    WHEN status IN ('sent', 'read') THEN 'sent'
                    ELSE 'received'
                END as type,
                CASE
                    WHEN TIMESTAMPDIFF(MINUTE, sent_date, NOW()) < 60 
                    THEN CONCAT(TIMESTAMPDIFF(MINUTE, sent_date, NOW()), ' minutes ago')
                    WHEN TIMESTAMPDIFF(HOUR, sent_date, NOW()) < 24 
                    THEN CONCAT(TIMESTAMPDIFF(HOUR, sent_date, NOW()), ' hours ago')
                    ELSE DATE_FORMAT(sent_date, '%b %d, %Y')
                END as time_ago
            FROM email_tracking et
            LEFT JOIN customers c ON et.customer_id = c.id
            LEFT JOIN companies comp ON et.company_id = comp.id
            ORDER BY sent_date DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveEmailTemplate($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO email_templates (name, subject, content, category, variables, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            subject = VALUES(subject),
            content = VALUES(content),
            category = VALUES(category),
            variables = VALUES(variables)
        ");
        return $stmt->execute([
            $data['name'],
            $data['subject'],
            $data['content'],
            $data['category'],
            json_encode($this->extractVariables($data['content'])),
            $_SESSION['user_id']
        ]);
    }

    private function extractVariables($content) {
        preg_match_all('/{([^}]+)}/', $content, $matches);
        return array_unique($matches[1]);
    }

    public function queueEmail($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO email_queue (
                template_id, customer_id, company_id, email_settings_id,
                subject, content, recipients, cc, bcc, scheduled_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['template_id'] ?? null,
            $data['customer_id'] ?? null,
            $data['company_id'] ?? null,
            $data['email_settings_id'],
            $data['subject'],
            $data['content'],
            $data['recipients'],
            $data['cc'] ?? null,
            $data['bcc'] ?? null,
            $data['scheduled_time'] ?? null
        ]);
    }

    public function processEmailQueue() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_queue 
            WHERE status = 'pending' 
            AND (scheduled_time IS NULL OR scheduled_time <= NOW())
            ORDER BY created_at ASC
            LIMIT 10
        ");
        $stmt->execute();
        $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($queue as $email) {
            try {
                $this->sendEmail($email);
                $this->updateQueueStatus($email['id'], 'sent');
            } catch (Exception $e) {
                $this->updateQueueStatus($email['id'], 'failed', $e->getMessage());
            }
        }
    }

    private function sendEmail($email) {
        // Get email settings
        $stmt = $this->pdo->prepare("SELECT * FROM email_settings WHERE id = ?");
        $stmt->execute([$email['email_settings_id']]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($settings['email_provider'] === 'gmail') {
            return $this->sendGmailEmail($email, $settings);
        } else {
            return $this->sendOutlookEmail($email, $settings);
        }
    }

    private function sendGmailEmail($email, $settings) {
        // Refresh token if needed
        if (strtotime($settings['token_expires']) <= time()) {
            $this->refreshGmailToken($settings);
        }

        $this->googleClient->setAccessToken($settings['access_token']);
        $service = new Google_Service_Gmail($this->googleClient);

        $message = new Google_Service_Gmail_Message();
        $rawMessage = $this->createEmail(
            $settings['email_address'],
            $email['recipients'],
            $email['subject'],
            $email['content'],
            $email['cc'],
            $email['bcc']
        );
        $message->setRaw(base64_encode($rawMessage));

        try {
            $sent = $service->users_messages->send('me', $message);
            $this->trackEmail($email, $sent->getId(), $settings['id']);
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to send email: ' . $e->getMessage());
        }
    }

    private function createEmail($from, $to, $subject, $content, $cc = null, $bcc = null) {
        $boundary = uniqid('boundary');
        $email = [
            "From: $from",
            "To: $to",
            $cc ? "Cc: $cc" : "",
            $bcc ? "Bcc: $bcc" : "",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=$boundary",
            "",
            "--$boundary",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode(strip_tags($content))),
            "",
            "--$boundary",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($content)),
            "",
            "--$boundary--"
        ];

        return implode("\r\n", array_filter($email));
    }

    private function trackEmail($email, $messageId, $settingsId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO email_tracking (
                customer_id, company_id, email_settings_id, message_id,
                subject, sender, recipients, content, sent_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'sent')
        ");
        $stmt->execute([
            $email['customer_id'],
            $email['company_id'],
            $settingsId,
            $messageId,
            $email['subject'],
            $email['sender'],
            $email['recipients'],
            $email['content']
        ]);
    }

    private function updateQueueStatus($id, $status, $error = null) {
        $stmt = $this->pdo->prepare("
            UPDATE email_queue 
            SET status = ?, error_message = ?, sent_time = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$status, $error, $id]);
    }
}
