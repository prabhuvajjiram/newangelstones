<?php
require_once 'config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;

class EmailManager {
    private $pdo;
    private $mail;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->mail = new PHPMailer(true);
        $this->loadConfig();
    }

    private function loadConfig() {
        if (!defined('GOOGLE_CLIENT_ID')) {
            throw new Exception('Gmail configuration not found. Please check auth_config.php');
        }
    }

    public function getAuthUrl() {
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => implode(' ', GMAIL_SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        return GMAIL_AUTH_URL . '?' . http_build_query($params);
    }

    public function getEmailPerformanceStats() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened_count,
                    SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked_count,
                    AVG(TIMESTAMPDIFF(MINUTE, sent_at, opened_at)) as avg_open_time
                FROM email_tracking
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $stats = $stmt->fetch();
            
            return [
                'total_sent' => $stats['total_sent'] ?? 0,
                'open_rate' => $stats['total_sent'] ? ($stats['opened_count'] / $stats['total_sent']) * 100 : 0,
                'click_rate' => $stats['total_sent'] ? ($stats['clicked_count'] / $stats['total_sent']) * 100 : 0,
                'avg_open_time' => $stats['avg_open_time'] ?? 0
            ];
        } catch (PDOException $e) {
            error_log("Error getting email performance stats: " . $e->getMessage());
            return [
                'total_sent' => 0,
                'open_rate' => 0,
                'click_rate' => 0,
                'avg_open_time' => 0
            ];
        }
    }

    public function getEmailActivityStats() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE(sent_at) as date,
                    COUNT(*) as sent_count,
                    SUM(CASE WHEN direction = 'received' THEN 1 ELSE 0 END) as received_count,
                    SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened_count
                FROM email_tracking
                WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(sent_at)
                ORDER BY date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting email activity stats: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentActivity($limit = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    et.*,
                    c.first_name || ' ' || c.last_name as contact
                FROM email_tracking et
                LEFT JOIN contacts c ON et.contact_id = c.id
                ORDER BY sent_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting recent activity: " . $e->getMessage());
            return [];
        }
    }

    public function sendEmail($to, $subject, $body, $access_token) {
        try {
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->Port = 587;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->SMTPAuth = true;
            $this->mail->AuthType = 'XOAUTH2';
            
            $this->mail->setOAuth(
                new OAuth([
                    'provider' => new Google([
                        'clientId' => GOOGLE_CLIENT_ID,
                        'clientSecret' => GOOGLE_CLIENT_SECRET
                    ]),
                    'clientId' => GOOGLE_CLIENT_ID,
                    'clientSecret' => GOOGLE_CLIENT_SECRET,
                    'refreshToken' => $_SESSION['email_refresh_token'] ?? null,
                    'userName' => $_SESSION['email'] ?? null
                ])
            );

            $this->mail->setFrom($_SESSION['email']);
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->isHTML(true);

            // Add tracking pixel
            $trackingId = uniqid('', true);
            $trackingPixel = '<img src="' . SITE_URL . CRM_PATH . '/track_email.php?id=' . $trackingId . '" width="1" height="1" />';
            $this->mail->Body .= $trackingPixel;

            if ($this->mail->send()) {
                // Log email sending
                $stmt = $this->pdo->prepare("
                    INSERT INTO email_tracking (tracking_id, to_email, subject, sent_at, direction)
                    VALUES (:tracking_id, :to_email, :subject, NOW(), 'sent')
                ");
                $stmt->execute([
                    'tracking_id' => $trackingId,
                    'to_email' => $to,
                    'subject' => $subject
                ]);
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            throw $e;
        }
    }
}
