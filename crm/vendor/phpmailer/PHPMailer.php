<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    const CHARSET_ASCII = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';
    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';

    public $Host = 'smtp.gmail.com';
    public $Port = 465;
    public $SMTPAuth = true;
    public $CharSet = 'UTF-8';
    public $SMTPSecure = 'ssl';
    public $From;
    public $FromName;
    public $Subject;
    public $Body;
    public $ErrorInfo;
    public $SMTPDebug = 0;
    public $Debugoutput = null;
    private $oauth;
    private $to = [];
    private $mailer = 'smtp';
    private $attachments = [];
    private $bcc = [];
    private $cc = [];

    public function isSMTP() {
        $this->mailer = 'smtp';
        return true;
    }

    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function addAddress($address, $name = '') {
        $this->to[] = ['address' => $address, 'name' => $name];
    }

    public function addCC($address, $name = '') {
        $this->cc[] = ['address' => $address, 'name' => $name];
    }

    public function addBCC($address, $name = '') {
        $this->bcc[] = ['address' => $address, 'name' => $name];
    }

    public function setOAuth($oauth) {
        $this->oauth = $oauth;
    }

    public function isHTML($isHtml = true) {
        return true;
    }

    public function addAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream') {
        if (file_exists($path)) {
            $this->attachments[] = [
                'path' => $path,
                'name' => $name ?: basename($path),
                'encoding' => $encoding,
                'type' => $type,
                'content' => file_get_contents($path)
            ];
            return true;
        }
        return false;
    }

    public function addStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream') {
        $this->attachments[] = [
            'content' => $string,
            'name' => $filename,
            'encoding' => $encoding,
            'type' => $type
        ];
        return true;
    }

    private function createBoundary() {
        return md5(uniqid(time()));
    }

    public function send() {
        if (!$this->oauth) {
            throw new \Exception('OAuth not configured');
        }

        try {
                // Get OAuth token
    $token = $this->oauth->getToken();
    if (!$token) {
        throw new \Exception('Failed to get OAuth token');
    }

    // Prepare email data
    $boundary = $this->createBoundary();
    
    // Format headers properly for Gmail API
    $headers = [
        'From: ' . ($this->FromName ? "{$this->FromName} <{$this->From}>" : $this->From),
        'To: ' . implode(', ', array_map(function($recipient) {
            return $recipient['name'] ? "{$recipient['name']} <{$recipient['address']}>" : $recipient['address'];
        }, $this->to))
    ];

    // Add CC if not empty
    if (!empty($this->cc)) {
        $headers[] = 'Cc: ' . implode(', ', array_map(function($recipient) {
            return is_array($recipient) ? ($recipient['name'] ? "{$recipient['name']} <{$recipient['address']}>" : $recipient['address']) : $recipient;
        }, $this->cc));
    }

    // Add BCC if not empty
    if (!empty($this->bcc)) {
        $headers[] = 'Bcc: ' . implode(', ', array_map(function($recipient) {
            return is_array($recipient) ? ($recipient['name'] ? "{$recipient['name']} <{$recipient['address']}>" : $recipient['address']) : $recipient;
        }, $this->bcc));
    }

    // Add remaining headers
    $headers = array_merge($headers, [
        'Subject: ' . $this->Subject,
        'MIME-Version: 1.0',
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        'Authorization: Bearer ' . $token
    ]);

    $message = implode("\r\n", $headers) . "\r\n\r\n";
    $message .= $this->createMessageBody($boundary);

            // Initialize cURL
            $ch = curl_init();

            // Set cURL options
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://www.googleapis.com/upload/gmail/v1/users/me/messages/send?uploadType=multipart',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: message/rfc822'
                ],
                CURLOPT_POSTFIELDS => $message
            ]);

            // Execute cURL request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Check for errors
            if (curl_errno($ch)) {
                throw new \Exception('cURL error: ' . curl_error($ch));
            }

            if ($httpCode !== 200) {
                throw new \Exception('Gmail API error: ' . $response);
            }

            curl_close($ch);
            return true;

        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            throw $e;
        }
    }

    private function createMessageBody($boundary) {
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset={$this->CharSet}\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($this->Body)) . "\r\n";

        foreach ($this->attachments as $attachment) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$attachment['type']}; name=\"{$attachment['name']}\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            
            $content = isset($attachment['path']) ? 
                file_get_contents($attachment['path']) : 
                $attachment['content'];
            
            $body .= chunk_split(base64_encode($content)) . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";
        return $body;
    }

    public function setSMTPDebug($level) {
        $this->SMTPDebug = $level;
        return $this;
    }

    public function setDebugOutput($callback) {
        $this->Debugoutput = $callback;
        return $this;
    }
}
