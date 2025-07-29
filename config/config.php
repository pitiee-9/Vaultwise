<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'vaultwise');
define('DB_USER', 'root');
define('DB_PASS', '');

// Plaid Configuration
define('PLAID_CLIENT_ID', 'your_plaid_client_id');
define('PLAID_SECRET', 'your_plaid_secret');
define('PLAID_ENV', 'sandbox'); // 'sandbox', 'development', or 'production'

// Africastalking Configuration
define('AT_API_KEY', 'atsk_f4a9fd8ce8e57cbba4c3df1cc06940af90d3ba5ecf7f959e731d1a0463f23816ed8f8b6d');
define('AT_USERNAME', 'sandbox');

// JWT Secret for token authentication
define('JWT_SECRET', '2e99a8f4935abe1caaa5eb5ac00c595d5d8b214213804da129629306663f10f9');

// Application Settings
define('BASE_URL', 'http://localhost/vaultwise/api/');
define('CORS_ORIGIN', 'http://localhost:3000'); // Your frontend URL

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set Headers for API
header("Access-Control-Allow-Origin: " . CORS_ORIGIN);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");