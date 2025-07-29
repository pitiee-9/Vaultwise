<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // User Registration
    if (isset($data['action']) && $data['action'] === 'register') {
        $database = new Database();
        $db = $database->getConnection();
        
        // Validate input
        $required = ['first_name', 'last_name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
                exit;
            }
        }
        
        // Check if email exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
            exit;
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users (first_name, last_name, email, password) 
                  VALUES (:first_name, :last_name, :email, :password)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashed_password);
        
        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            
            // Create wallet for user
            $query = "INSERT INTO wallets (user_id, balance) VALUES (:user_id, 0.00)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            // Generate JWT
            $token = generateJWT($user_id);
            
            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user_id' => $user_id,
                'token' => $token
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
        }
    }
    
    // User Login
    elseif (isset($data['action']) && $data['action'] === 'login') {
        $database = new Database();
        $db = $database->getConnection();
        
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
            exit;
        }
        
        // Check credentials
        $query = "SELECT id, password FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $data['email']);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $row['id'];
            
            if (password_verify($data['password'], $row['password'])) {
                // Generate JWT
                $token = generateJWT($user_id);
                
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user_id' => $user_id,
                    'token' => $token
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
            }
        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

// Generate JWT token
function generateJWT($user_id) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user_id,
        'exp' => time() + (60 * 60 * 24) // 24 hours expiration
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}