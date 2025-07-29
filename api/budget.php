<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/ai_helper.php';
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
$aiHelper = new AIHelper();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user budgets
    if (isset($_GET['action']) && $_GET['action'] === 'get_budgets') {
        $query = "SELECT * FROM budgets WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get current spending for each budget category
        $currentMonth = date('Y-m');
        foreach ($budgets as &$budget) {
            $query = "SELECT COALESCE(SUM(amount), 0) as total 
                      FROM transactions t
                      JOIN accounts a ON t.account_id = a.id
                      JOIN banks b ON a.bank_id = b.id
                      WHERE b.user_id = :user_id
                      AND t.category = :category
                      AND DATE_FORMAT(t.date, '%Y-%m') = :current_month";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':category', $budget['category']);
            $stmt->bindParam(':current_month', $currentMonth);
            $stmt->execute();
            
            $spending = $stmt->fetch(PDO::FETCH_ASSOC);
            $budget['current_spending'] = $spending['total'];
        }
        
        echo json_encode([
            'status' => 'success',
            'budgets' => $budgets
        ]);
    }
    
    // Get AI insights
    elseif (isset($_GET['action']) && $_GET['action'] === 'get_insights') {
        $query = "SELECT * FROM ai_insights WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $insights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no insights, generate new ones
        if (empty($insights)) {
            $insights = $aiHelper->generateInsights($user_id);
        }
        
        echo json_encode([
            'status' => 'success',
            'insights' => $insights
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Create or update budget
    if (isset($data['action']) && $data['action'] === 'save_budget') {
        if (empty($data['category']) || !isset($data['amount'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        
        // Check if budget exists
        $query = "SELECT id FROM budgets 
                  WHERE user_id = :user_id AND category = :category";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':category', $data['category']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update existing budget
            $budget = $stmt->fetch(PDO::FETCH_ASSOC);
            $query = "UPDATE budgets SET amount = :amount, period = :period 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $budget['id']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':period', $data['period'] ?? 'monthly');
        } else {
            // Create new budget
            $query = "INSERT INTO budgets (user_id, category, amount, period) 
                      VALUES (:user_id, :category, :amount, :period)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':category', $data['category']);
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':period', $data['period'] ?? 'monthly');
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Budget saved successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save budget']);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}