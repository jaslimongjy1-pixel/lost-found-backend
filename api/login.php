<?php


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once "connection.php";

$data = json_decode(file_get_contents("php://input"), true);

// 1. Validation
if (empty($data["user_email"]) || empty($data["user_password"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password are required"
    ]);
    exit;
}

$email = trim($data["user_email"]);
$password = $data["user_password"];

try {
    // 2. Query based on  users table schema
    $stmt = $db->prepare("SELECT * FROM users WHERE user_email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Verify password
    if (!$user || !password_verify($password, $user["user_password"])) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email or password"
        ]);
        exit;
    }

    // 4. Clean user object (remove sensitive data)
    unset($user["user_password"]);

    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "user" => $user
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>