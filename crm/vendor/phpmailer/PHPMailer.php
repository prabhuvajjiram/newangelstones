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
    public $Port = 587;
    public $SMTPAuth = true;
    public $CharSet = 'UTF-8';
    public $SMTPSecure = 'tls';
    public $From;
    public $FromName;
    public $Subject;
    public $Body;
    public $ErrorInfo;
    private $oauth;
    private $to = [];
    private $mailer = 'smtp';

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

    public function setOAuth($oauth) {
        $this->oauth = $oauth;
    }

    public function isHTML($isHtml = true) {
        // Implementation for HTML emails
        return true;
    }

    public function send() {
        if (!$this->oauth) {
            throw new \Exception('OAuth not configured');
        }

        if ($this->mailer !== 'smtp') {
            throw new \Exception('Only SMTP mailer is supported');
        }

        $to = implode(', ', array_map(function($recipient) {
            return $recipient['name'] ? "{$recipient['name']} <{$recipient['address']}>" : $recipient['address'];
        }, $this->to));

        $headers = [
            'From: ' . ($this->FromName ? "{$this->FromName} <{$this->From}>" : $this->From),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=' . $this->CharSet,
            'Authorization: Bearer ' . $this->oauth->getToken()
        ];

        return mail($to, $this->Subject, $this->Body, implode("\r\n", $headers));
    }
}
