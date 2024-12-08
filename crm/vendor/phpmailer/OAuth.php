<?php
namespace PHPMailer\PHPMailer;

class OAuth {
    private $provider;
    private $clientId;
    private $clientSecret;
    private $refreshToken;
    private $userName;
    private $accessToken;

    public function __construct($options) {
        $this->provider = $options['provider'] ?? null;
        $this->clientId = $options['clientId'] ?? '';
        $this->clientSecret = $options['clientSecret'] ?? '';
        $this->refreshToken = $options['refreshToken'] ?? '';
        $this->userName = $options['userName'] ?? '';
        $this->accessToken = $options['accessToken'] ?? '';
    }

    public function getToken() {
        if (!$this->accessToken && $this->refreshToken) {
            // Refresh the token using the provider if available
            if ($this->provider) {
                try {
                    $token = $this->provider->getAccessToken('refresh_token', [
                        'refresh_token' => $this->refreshToken
                    ]);
                    $this->accessToken = $token->getToken();
                } catch (\Exception $e) {
                    throw new \Exception('Error refreshing OAuth token: ' . $e->getMessage());
                }
            } else {
                // Manual token refresh if no provider is available
                $ch = curl_init('https://oauth2.googleapis.com/token');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token'
                ]));

                $response = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    throw new \Exception('Error refreshing token: ' . $error);
                }

                $data = json_decode($response, true);
                if (!isset($data['access_token'])) {
                    throw new \Exception('Invalid refresh token response');
                }

                $this->accessToken = $data['access_token'];
            }
        }

        return $this->accessToken;
    }
}
