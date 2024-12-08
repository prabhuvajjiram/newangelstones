<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
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

    public function isSMTP() {
        return true;
    }

    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
    }

    public function addAddress($address) {
        $this->to[] = $address;
    }

    public function setOAuth($oauth) {
        $this->oauth = $oauth;
    }

    public function send() {
        try {
            $token = $this->oauth->getToken();
            
            $raw = "From: {$this->From}\r\n";
            foreach ($this->to as $to) {
                $raw .= "To: $to\r\n";
            }
            $raw .= 'Subject: =?utf-8?B?' . base64_encode($this->Subject) . "?=\r\n";
            $raw .= "MIME-Version: 1.0\r\n";
            $raw .= "Content-Type: text/plain; charset=utf-8\r\n";
            $raw .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $raw .= base64_encode($this->Body);
            
            $encoded_message = strtr(base64_encode($raw), array('+' => '-', '/' => '_'));
            
            $headers = [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ];
            
            $post_data = json_encode([
                'raw' => $encoded_message
            ]);
            
            $ch = curl_init('https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                $this->ErrorInfo = 'cURL Error: ' . $error;
                return false;
            }
            
            if ($http_code !== 200) {
                $this->ErrorInfo = 'Gmail API Error: ' . $response;
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }
}
