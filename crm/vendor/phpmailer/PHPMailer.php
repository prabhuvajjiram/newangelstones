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
    public $Username;
    public $Password;
    public $CharSet = 'UTF-8';
    public $SMTPSecure = 'tls';
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
        try {
            if ($this->oauth) {
                return $this->sendWithOAuth();
            } else {
                return $this->sendWithSMTP();
            }
        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            throw $e;
        }
    }

    private function sendWithSMTP() {
        if (empty($this->Username) || empty($this->Password)) {
            throw new \Exception('SMTP credentials not configured');
        }

        // Create SSL/TLS context
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        // Create socket with proper context
        $socket = stream_socket_client(
            ($this->SMTPSecure === 'ssl' ? 'ssl://' : 'tcp://') . $this->Host . ':' . $this->Port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new \Exception("Failed to connect to server: $errstr ($errno)");
        }

        // Set socket timeout
        stream_set_timeout($socket, 30);

        // Read greeting
        $this->readResponse($socket);

        // Send EHLO
        $this->sendCommand($socket, "EHLO " . gethostname());

        // Start TLS if using TLS
        if ($this->SMTPSecure === 'tls') {
            $this->sendCommand($socket, "STARTTLS");
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \Exception('Failed to enable TLS encryption');
            }
            $this->sendCommand($socket, "EHLO " . gethostname());
        }

        // Authenticate
        $this->sendCommand($socket, "AUTH LOGIN");
        $this->sendCommand($socket, base64_encode($this->Username));
        $this->sendCommand($socket, base64_encode($this->Password));

        // Send From
        $this->sendCommand($socket, "MAIL FROM:<{$this->From}>");

        // Send To
        foreach ($this->to as $recipient) {
            $this->sendCommand($socket, "RCPT TO:<{$recipient['address']}>");
        }

        // Send Data
        $this->sendCommand($socket, "DATA");

        // Create message
        $boundary = $this->createBoundary();
        $message = $this->createHeaders($boundary);
        $message .= $this->createMessageBody($boundary);

        // Send message and finish
        $this->sendCommand($socket, $message . "\r\n.");
        $this->sendCommand($socket, "QUIT");

        fclose($socket);
        return true;
    }

    private function sendCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return $this->readResponse($socket);
    }

    private function readResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        if (substr($response, 0, 3) >= '400') {
            throw new \Exception("SMTP Error: " . trim($response));
        }
        return $response;
    }

    private function createHeaders($boundary) {
        $headers = [
            "Date: " . date("r"),
            "To: " . implode(", ", array_map(function($recipient) {
                return $recipient['name'] ? "{$recipient['name']} <{$recipient['address']}>" : $recipient['address'];
            }, $this->to)),
            "From: " . ($this->FromName ? "{$this->FromName} <{$this->From}>" : $this->From),
            "Subject: " . $this->Subject,
            "MIME-Version: 1.0",
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\""
        ];

        if (!empty($this->cc)) {
            $headers[] = "Cc: " . implode(", ", array_map(function($recipient) {
                return $recipient['name'] ? "{$recipient['name']} <{$recipient['address']}>" : $recipient['address'];
            }, $this->cc));
        }

        return implode("\r\n", $headers) . "\r\n\r\n";
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
