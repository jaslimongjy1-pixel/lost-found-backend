<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle pre-flight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once "connection.php";

// Get raw POST data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 1. Strict validation 
if (empty($data["user_name"]) || empty($data["user_email"]) || empty($data["user_password"])) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

$name = trim($data["user_name"]);
$email = trim($data["user_email"]);
$password = $data["user_password"];
$phone = isset($data["user_phone"]) ? trim($data["user_phone"]) : '';
// Default role to 'student' if not provided
$role = isset($data["user_role"]) ? trim($data["user_role"]) : 'student';

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit;
}

// Optional: Validate phone format if provided
if (!empty($phone) && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
    echo json_encode(["status" => "error", "message" => "Invalid phone number format"]);
    exit;
}

// 2. Security: Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$createdAt = date('Y-m-d H:i:s');

try {
    // 3. Check for duplicates
    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Email already registered"]);
        exit;
    }

    // 4. Insert user (Removed transaction to avoid file locks)
    $insert = $db->prepare("
        INSERT INTO users (user_name, user_email, user_phone, user_password, user_date, user_role)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $insert->execute([$name, $email, $phone, $hashedPassword, $createdAt, $role]);

    echo json_encode([
        "status" => "success",
        "message" => "Account created successfully."
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>