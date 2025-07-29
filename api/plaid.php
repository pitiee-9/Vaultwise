<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/plaid_helper.php';
require_once __DIR__ . '/../config/config.php';

// Verify JWT token
$token = getBearerToken();
if (!$token || !validateJWT($token)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$payload = getJWTData($token);
$user_id = $payload['user_id'];

$database = new Database();
$db = $database->getConnection();
$plaidHelper = new PlaidHelper();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Create link token for Plaid Link
    if (isset($data['action']) && $data['action'] === 'create_link_token') {
        $response = $plaidHelper->createLinkToken($user_id);
        
        if ($response['status'] === 'success') {
            echo json_encode([
                'status' => 'success',
                'link_token' => $response['data']['link_token']
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to create link token',
                'plaid_error' => $response['data']
            ]);
        }
    }
    
    // Exchange public token for access token
    elseif (isset($data['action']) && $data['action'] === 'exchange_public_token') {
        if (empty($data['public_token']) || empty($data['institution_name']) || empty($data['institution_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        $response = $plaidHelper->exchangePublicToken($data['public_token']);
        
        if ($response['status'] === 'success') {
            $access_token = $response['data']['access_token'];
            $item_id = $response['data']['item_id'];
            
            // Save bank connection
            $query = "INSERT INTO banks (user_id, plaid_access_token, plaid_item_id, institution_name) 
                      VALUES (:user_id, :access_token, :item_id, :institution_name)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':access_token', $access_token);
            $stmt->bindParam(':item_id', $item_id);
            $stmt->bindParam(':institution_name', $data['institution_name']);
            
            if ($stmt->execute()) {
                $bank_id = $db->lastInsertId();
                
                // Get accounts and save them
                $accountsResponse = $plaidHelper->getAccounts($access_token);
                
                if ($accountsResponse['status'] === 'success') {
                    foreach ($accountsResponse['data']['accounts'] as $account) {
                        $query = "INSERT INTO accounts (bank_id, account_id, name, type, balance) 
                                  VALUES (:bank_id, :account_id, :name, :type, :balance)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':bank_id', $bank_id);
                        $stmt->bindParam(':account_id', $account['account_id']);
                        $stmt->bindParam(':name', $account['name']);
                        $stmt->bindParam(':type', $account['type']);
                        $stmt->bindParam(':balance', $account['balances']['current']);
                        $stmt->execute();
                    }
                    
                    // Get recent transactions (last 30 days)
                    $end_date = date('Y-m-d');
                    $start_date = date('Y-m-d', strtotime('-30 days'));
                    $transactionsResponse = $plaidHelper->getTransactions($access_token, $start_date, $end_date);
                    
                    if ($transactionsResponse['status'] === 'success') {
                        foreach ($transactionsResponse['data']['transactions'] as $transaction) {
                            $query = "INSERT INTO transactions (account_id, transaction_id, amount, date, name, merchant_name, category, pending) 
                                      VALUES (:account_id, :transaction_id, :amount, :date, :name, :merchant_name, :category, :pending)";
                            $stmt = $db->prepare($query);
                            
                            // Find account ID
                            $accountStmt = $db->prepare("SELECT id FROM accounts WHERE account_id = :account_id");
                            $accountStmt->bindParam(':account_id', $transaction['account_id']);
                            $accountStmt->execute();
                            $account = $accountStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($account) {
                                $stmt->bindParam(':account_id', $account['id']);
                                $stmt->bindParam(':transaction_id', $transaction['transaction_id']);
                                $stmt->bindParam(':amount', $transaction['amount']);
                                $stmt->bindParam(':date', $transaction['date']);
                                $stmt->bindParam(':name', $transaction['name']);
                                $stmt->bindParam(':merchant_name', $transaction['merchant_name']);
                                $stmt->bindValue(':category', $transaction['category'][0] ?? 'uncategorized');
                                $stmt->bindParam(':pending', $transaction['pending'], PDO::PARAM_BOOL);
                                $stmt->execute();
                            }
                        }
                    }
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Bank account connected successfully',
                        'bank_id' => $bank_id
                    ]);
                } else {
                    // Rollback bank insertion if accounts failed
                    $db->prepare("DELETE FROM banks WHERE id = :bank_id")->execute([':bank_id' => $bank_id]);
                    
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to fetch accounts',
                        'plaid_error' => $accountsResponse['data']
                    ]);
                }
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save bank connection']);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to exchange public token',
                'plaid_error' => $response['data']
            ]);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

// Helper functions for JWT
function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function validateJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    
    $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return hash_equals($base64UrlSignature, $parts[2]);
}

function getJWTData($token) {
    $parts = explode('.', $token);
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
    return json_decode($payload, true);
}