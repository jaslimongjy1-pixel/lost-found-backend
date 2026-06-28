<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "connection.php";
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);
$userId = isset($data['user_id']) ? intval($data['user_id']) : null;
$userRole = isset($data['user_role']) ? strtolower(trim($data['user_role'])) : '';

if ($userId === null) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "User ID is required. Received: " . $jsonInput]);
    exit;
}

try {
    if ($userRole === 'admin' || $userRole === 'lecturer') {
        $stmt = $db->prepare(
            "SELECT report_id, user_id, report_type, report_title, report_description, report_category, report_location, report_status, report_image, report_date FROM reports ORDER BY report_id DESC"
        );
        $stmt->execute();
    } else {
        $stmt = $db->prepare(
            "SELECT report_id, user_id, report_type, report_title, report_description, report_category, report_location, report_status, report_image, report_date
             FROM reports
             WHERE user_id = :user_id
                OR (user_id != :user_id AND LOWER(report_status) = 'approved')
             ORDER BY report_id DESC"
        );
        $stmt->execute([':user_id' => $userId]);
    }

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "reports" => $reports
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
