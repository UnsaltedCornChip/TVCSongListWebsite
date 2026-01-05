<?php
// api/healthcheck.php

header('Content-Type: application/json; charset=utf-8');

// Optional: Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed. Use GET.'
    ]);
    exit;
}

try {
    // Database connection (same as your other files)
    $host = getenv('DB_HOST');
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $sslmode = 'require';

    $dsn = "pgsql:host=$host;dbname=$dbname;sslmode=$sslmode";

    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Simple test query
    $stmt = $pdo->query("SELECT 1 AS status");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // If we got here → connection + query works
    http_response_code(200);
    echo json_encode([
        'status' => 'healthy',
        'database' => 'connected',
        'timestamp' => date('c'),
        'test_result' => $result['status'] // should be 1
    ]);

} catch (PDOException $e) {
    http_response_code(503); // Service Unavailable
    echo json_encode([
        'status' => 'unhealthy',
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'unhealthy',
        'message' => 'Unexpected error',
        'timestamp' => date('c')
    ]);
}