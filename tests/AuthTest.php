<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $db;
    private $pdo;

    protected function setUp(): void
    {
        // Set up test database connection
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create test tables
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL
            );
            
            CREATE TABLE wallets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                balance REAL NOT NULL DEFAULT 0.00,
                FOREIGN KEY(user_id) REFERENCES users(id)
            );
        ");
        
        // Initialize database helper with test connection
        $this->db = new Database($this->pdo);
    }

    public function testSuccessfulRegistration()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $data = [
            'action' => 'register',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        // Capture output
        ob_start();
        include __DIR__ . '/../api/auth.php';
        $output = json_decode(ob_get_clean(), true);
        
        $this->assertEquals('success', $output['status']);
        $this->assertArrayHasKey('user_id', $output);
        $this->assertArrayHasKey('token', $output);
        
        // Verify user was created in database
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['test@example.com']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($user);
        $this->assertEquals('Test', $user['first_name']);
    }

    public function testRegistrationMissingFields()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $data = [
            'action' => 'register',
            'first_name' => 'Test',
            // Missing last_name, email, password
        ];
        
        // Capture output
        ob_start();
        include __DIR__ . '/../api/auth.php';
        $output = json_decode(ob_get_clean(), true);
        
        $this->assertEquals(400, http_response_code());
        $this->assertEquals('error', $output['status']);
        $this->assertStringContainsString('Missing required field', $output['message']);
    }

    public function testDuplicateEmailRegistration()
    {
        // First create a user
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES ('Existing', 'User', 'exists@example.com', 'hashed_password')
        ");
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $data = [
            'action' => 'register',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'exists@example.com', // Duplicate email
            'password' => 'password123'
        ];
        
        // Capture output
        ob_start();
        include __DIR__ . '/../api/auth.php';
        $output = json_decode(ob_get_clean(), true);
        
        $this->assertEquals(409, http_response_code());
        $this->assertEquals('error', $output['status']);
        $this->assertEquals('Email already exists', $output['message']);
    }

    public function testSuccessfulLogin()
    {
        // First create a test user
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES ('Test', 'User', 'test@example.com', '$hashedPassword')
        ");
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $data = [
            'action' => 'login',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        // Capture output
        ob_start();
        include __DIR__ . '/../api/auth.php';
        $output = json_decode(ob_get_clean(), true);
        
        $this->assertEquals(200, http_response_code());
        $this->assertEquals('success', $output['status']);
        $this->assertArrayHasKey('user_id', $output);
        $this->assertArrayHasKey('token', $output);
    }

    public function testLoginMissingCredentials()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $data = [
            'action' => 'login',
            // Missing email and password
        ];
        
        // Capture output
        ob_start();
        include __DIR__ . '/../api/auth.php';
        $output = json_decode(ob_get_clean(), true);
        
        $this->assertEquals(400, http_response_code());
        $this->assertEquals('error', $output['status']);
        $this->assertEquals('Email and password are required', $output['message']);
    }

    public function testLoginInvalidCredentials()
    {
        // Create test user
        $hashedPassword = password_hash('correctpassword', PASSWORD_DEFAULT);
        $this->pdo->exec("
            INSERT INTO users (first_name, last_name, email, password)
            VALUES ('Test', 'User', 'test@example.com', '$hashedPassword')
        ");
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $data = [
            'action' => 'login',
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];
        
        // Capture output
        ob_start();
        include __DIR__ . '/../api/auth.php';
        $output = json_decode(ob_get_clean(), true);
        
        $this->assertEquals(401, http_response_code());
        $this->assertEquals('error', $output['status']);
        $this->assertEquals('Invalid credentials', $output['message']);
    }
}