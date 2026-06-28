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

function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(["status" => "error", "message" => $message]);
    exit;
}


$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    $data = $_POST;
}


$reportId = isset($data['report_id']) ? intval($data['report_id']) : null;
$userId = isset($data['user_id']) ? intval($data['user_id']) : null;
$userRole = isset($data['user_role']) ? strtolower(trim($data['user_role'])) : '';


if (!$reportId) {
    json_error("Report ID is required (received: " . print_r($data, true) . ")");
}
if (!$userId) {
    json_error("User authentication is required");
}

$uploadDir = __DIR__ . "/../uploads/reports/";

try {
 
    $stmt = $db->prepare("SELECT user_id, report_image FROM reports WHERE report_id = ? LIMIT 1");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$report) {
        json_error("Report not found", 404);
    }

    $ownerId = $report['user_id'] ?? 0;
    $isOwner = ($ownerId == $userId);
    $isAdmin = ($userRole == 'lecturer' || $userRole == 'admin');

    if (!$isOwner && !$isAdmin) {
        json_error('Forbidden: You are not the owner or an admin.', 403);
    }

   
    $imageName = (string) ($report["report_image"] ?? "");
    $imagePath = $uploadDir . $imageName;

    if ($imageName !== "" && file_exists($imagePath)) {
        unlink($imagePath);
    }

   
    $stmt = $db->prepare("DELETE FROM reports WHERE report_id = ?");
    $stmt->execute([$reportId]);

    echo json_encode([
        "status" => "success",
        "message" => "Report deleted successfully"
    ]);
} catch (Throwable $e) {
    json_error($e->getMessage(), 500);
}
?>