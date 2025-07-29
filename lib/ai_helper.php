<?php
require_once __DIR__ . '/db.php';

class AIHelper {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function generateInsights($user_id) {
        $insights = [];
        
        // 1. Analyze spending patterns
        $spendingInsight = $this->analyzeSpendingPatterns($user_id);
        if ($spendingInsight) {
            $insights[] = [
                'user_id' => $user_id,
                'insight_text' => $spendingInsight,
                'insight_type' => 'spending'
            ];
        }
        
        // 2. Savings opportunity
        $savingsInsight = $this->findSavingsOpportunity($user_id);
        if ($savingsInsight) {
            $insights[] = [
                'user_id' => $user_id,
                'insight_text' => $savingsInsight,
                'insight_type' => 'savings'
            ];
        }
        
        // 3. Investment suggestion
        $investmentInsight = $this->generateInvestmentSuggestion($user_id);
        if ($investmentInsight) {
            $insights[] = [
                'user_id' => $user_id,
                'insight_text' => $investmentInsight,
                'insight_type' => 'investment'
            ];
        }
        
        // Save insights to database
        foreach ($insights as $insight) {
            $query = "INSERT INTO ai_insights (user_id, insight_text, insight_type) 
                      VALUES (:user_id, :insight_text, :insight_type)";
            $stmt = $this->db->prepare($query);
            $stmt->execute($insight);
        }
        
        return $insights;
    }
    
    private function analyzeSpendingPatterns($user_id) {
        // Get spending by category for current and previous month
        $currentMonth = date('Y-m');
        $prevMonth = date('Y-m', strtotime('-1 month'));
        
        $query = "SELECT category, SUM(amount) as total 
                  FROM transactions t
                  JOIN accounts a ON t.account_id = a.id
                  JOIN banks b ON a.bank_id = b.id
                  WHERE b.user_id = :user_id
                  AND DATE_FORMAT(t.date, '%Y-%m') = :month
                  GROUP BY category";
        
        $currentStmt = $this->db->prepare($query);
        $currentStmt->bindParam(':user_id', $user_id);
        $currentStmt->bindParam(':month', $currentMonth);
        $currentStmt->execute();
        $currentSpending = $currentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $prevStmt = $this->db->prepare($query);
        $prevStmt->bindParam(':user_id', $user_id);
        $prevStmt->bindParam(':month', $prevMonth);
        $prevStmt->execute();
        $prevSpending = $prevStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Identify significant changes
        $insights = [];
        foreach ($currentSpending as $category => $amount) {
            $prevAmount = $prevSpending[$category] ?? 0;
            
            if ($prevAmount > 0) {
                $change = (($amount - $prevAmount) / $prevAmount) * 100;
                
                if (abs($change) > 20) { // 20% change threshold
                    $trend = $change > 0 ? 'increased' : 'decreased';
                    $insights[] = "Your {$category} spending {$trend} by " . abs(round($change)) . "% compared to last month.";
                }
            }
        }
        
        return implode(' ', $insights);
    }
    
    private function findSavingsOpportunity($user_id) {
        // Get discretionary spending (entertainment, dining, shopping)
        $query = "SELECT SUM(amount) as total 
                  FROM transactions t
                  JOIN accounts a ON t.account_id = a.id
                  JOIN banks b ON a.bank_id = b.id
                  WHERE b.user_id = :user_id
                  AND t.category IN ('entertainment', 'dining', 'shopping')
                  AND DATE_FORMAT(t.date, '%Y-%m') = :month";
        
        $stmt = $this->db->prepare($query);
        $month = date('Y-m');
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':month', $month);
        $stmt->execute();
        
        $discretionary = $stmt->fetch(PDO::FETCH_COLUMN);
        
        if ($discretionary > 300) { // Threshold for savings opportunity
            $savings = round($discretionary * 0.15); // Suggest saving 15%
            return "You're spending \${$discretionary} on discretionary categories this month. Consider reducing by 15% (\${$savings}) to boost your savings.";
        }
        
        return null;
    }
    
    private function generateInvestmentSuggestion($user_id) {
        // Get average monthly savings
        $query = "SELECT AVG(balance) as avg_balance 
                  FROM wallets 
                  WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $avgBalance = $stmt->fetch(PDO::FETCH_COLUMN);
        
        if ($avgBalance > 500) {
            $investment = round($avgBalance * 0.2); // Suggest investing 20%
            return "Based on your average wallet balance of \${$avgBalance}, consider investing \${$investment} in a diversified ETF for potential long-term growth.";
        }
        
        return null;
    }
}