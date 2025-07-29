<?php
require_once __DIR__ . '/../config/config.php';

class PlaidHelper {
    private $client_id;
    private $secret;
    private $env;
    
    public function __construct() {
        $this->client_id = PLAID_CLIENT_ID;
        $this->secret = PLAID_SECRET;
        $this->env = PLAID_ENV;
    }
    
    public function createLinkToken($user_id) {
        $url = "https://" . $this->env . ".plaid.com/link/token/create";
        $payload = json_encode([
            'client_id' => $this->client_id,
            'secret' => $this->secret,
            'client_name' => 'VaultWise',
            'user' => ['client_user_id' => (string)$user_id],
            'products' => ['auth', 'transactions'],
            'country_codes' => ['US'],
            'language' => 'en'
        ]);
        
        return $this->makeRequest($url, $payload);
    }
    
    public function exchangePublicToken($public_token) {
        $url = "https://" . $this->env . ".plaid.com/item/public_token/exchange";
        $payload = json_encode([
            'client_id' => $this->client_id,
            'secret' => $this->secret,
            'public_token' => $public_token
        ]);
        
        return $this->makeRequest($url, $payload);
    }
    
    public function getAccounts($access_token) {
        $url = "https://" . $this->env . ".plaid.com/accounts/get";
        $payload = json_encode([
            'client_id' => $this->client_id,
            'secret' => $this->secret,
            'access_token' => $access_token
        ]);
        
        return $this->makeRequest($url, $payload);
    }
    
    public function getTransactions($access_token, $start_date, $end_date) {
        $url = "https://" . $this->env . ".plaid.com/transactions/get";
        $payload = json_encode([
            'client_id' => $this->client_id,
            'secret' => $this->secret,
            'access_token' => $access_token,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'options' => [
                'count' => 100,
                'offset' => 0
            ]
        ]);
        
        return $this->makeRequest($url, $payload);
    }
    
    private function makeRequest($url, $payload) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $http_code == 200 ? 'success' : 'error',
            'code' => $http_code,
            'data' => json_decode($response, true)
        ];
    }
}