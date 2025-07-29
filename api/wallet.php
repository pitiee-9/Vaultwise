<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/africastalking_helper.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

function validateJWT($token) {
    // Very basic example: adjust for real validation using secret key
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        return true; // fake valid, just format check
    }
    return false;
}

// Add this before you call the function
function getBearerToken() {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authorization header not found']);
        exit;
    }
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        return $matches[1];
    }
    http_response_code(401);
    echo json_encode(['error' => 'Invalid authorization format']);
    exit;
}

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
$atHelper = new AfricastalkingHelper();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get wallet balance
    if (isset($_GET['action']) && $_GET['action'] === 'balance') {
        $query = "SELECT balance FROM wallets WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'status' => 'success',
                'balance' => $wallet['balance']
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Wallet not found']);
        }
    }
    
    // Get wallet transactions
    elseif (isset($_GET['action']) && $_GET['action'] === 'transactions') {
        $query = "SELECT wt.* FROM wallet_transactions wt
                  JOIN wallets w ON wt.wallet_id = w.id
                  WHERE w.user_id = :user_id
                  ORDER BY wt.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'status' => 'success',
            'transactions' => $transactions
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Add funds to wallet
    if (isset($data['action']) && $data['action'] === 'add_funds') {
        if (empty($data['amount']) || empty($data['card_token'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        $amount = floatval($data['amount']);
        
        // Process payment with Africastalking
        $paymentResult = $atHelper->processCardPayment($data['card_token'], $amount);
        
        if ($paymentResult['status'] === 'success') {
            // Update wallet balance
            $db->beginTransaction();
            
            try {
                // Get wallet ID
                $walletQuery = "SELECT id FROM wallets WHERE user_id = :user_id FOR UPDATE";
                $walletStmt = $db->prepare($walletQuery);
                $walletStmt->bindParam(':user_id', $user_id);
                $walletStmt->execute();
                $wallet = $walletStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$wallet) {
                    throw new Exception('Wallet not found');
                }
                
                // Update balance
                $updateQuery = "UPDATE wallets SET balance = balance + :amount WHERE id = :wallet_id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':amount', $amount);
                $updateStmt->bindParam(':wallet_id', $wallet['id']);
                $updateStmt->execute();
                
                // Record transaction
                $transQuery = "INSERT INTO wallet_transactions (wallet_id, amount, type, description, reference) 
                               VALUES (:wallet_id, :amount, 'credit', 'Wallet top-up', :reference)";
                $transStmt = $db->prepare($transQuery);
                $transStmt->bindParam(':wallet_id', $wallet['id']);
                $transStmt->bindParam(':amount', $amount);
                $transStmt->bindParam(':reference', $paymentResult['transaction_id']);
                $transStmt->execute();
                
                $db->commit();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Funds added to wallet',
                    'new_balance' => $wallet['balance'] + $amount,
                    'transaction_id' => $paymentResult['transaction_id']
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Payment processing failed',
                'payment_error' => $paymentResult['error']
            ]);
        }
    }
    
    // Send money to another user
    elseif (isset($data['action']) && $data['action'] === 'send_money') {
        if (empty($data['amount']) || empty($data['recipient_id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        $amount = floatval($data['amount']);
        $recipient_id = $data['recipient_id'];
        
        $db->beginTransaction();
        
        try {
            // Get sender wallet
            $senderQuery = "SELECT id, balance FROM wallets WHERE user_id = :user_id FOR UPDATE";
            $senderStmt = $db->prepare($senderQuery);
            $senderStmt->bindParam(':user_id', $user_id);
            $senderStmt->execute();
            $senderWallet = $senderStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$senderWallet) {
                throw new Exception('Sender wallet not found');
            }
            
            // Check balance
            if ($senderWallet['balance'] < $amount) {
                throw new Exception('Insufficient funds');
            }
            
            // Get recipient wallet
            $recipientQuery = "SELECT id FROM wallets WHERE user_id = :recipient_id FOR UPDATE";
            $recipientStmt = $db->prepare($recipientQuery);
            $recipientStmt->bindParam(':recipient_id', $recipient_id);
            $recipientStmt->execute();
            $recipientWallet = $recipientStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recipientWallet) {
                throw new Exception('Recipient wallet not found');
            }
            
            // Update sender balance
            $updateSenderQuery = "UPDATE wallets SET balance = balance - :amount WHERE id = :wallet_id";
            $updateSenderStmt = $db->prepare($updateSenderQuery);
            $updateSenderStmt->bindParam(':amount', $amount);
            $updateSenderStmt->bindParam(':wallet_id', $senderWallet['id']);
            $updateSenderStmt->execute();
            
            // Record sender transaction
            $senderTransQuery = "INSERT INTO wallet_transactions (wallet_id, amount, type, description) 
                                 VALUES (:wallet_id, :amount, 'debit', 'Sent to user #{$recipient_id}')";
            $senderTransStmt = $db->prepare($senderTransQuery);
            $senderTransStmt->bindParam(':wallet_id', $senderWallet['id']);
            $senderTransStmt->bindParam(':amount', $amount);
            $senderTransStmt->execute();
            
            // Update recipient balance
            $updateRecipientQuery = "UPDATE wallets SET balance = balance + :amount WHERE id = :wallet_id";
            $updateRecipientStmt = $db->prepare($updateRecipientQuery);
            $updateRecipientStmt->bindParam(':amount', $amount);
            $updateRecipientStmt->bindParam(':wallet_id', $recipientWallet['id']);
            $updateRecipientStmt->execute();
            
            // Record recipient transaction
            $recipientTransQuery = "INSERT INTO wallet_transactions (wallet_id, amount, type, description) 
                                    VALUES (:wallet_id, :amount, 'credit', 'Received from user #{$user_id}')";
            $recipientTransStmt = $db->prepare($recipientTransQuery);
            $recipientTransStmt->bindParam(':wallet_id', $recipientWallet['id']);
            $recipientTransStmt->bindParam(':amount', $amount);
            $recipientTransStmt->execute();
            
            $db->commit();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Money sent successfully',
                'new_balance' => $senderWallet['balance'] - $amount
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}