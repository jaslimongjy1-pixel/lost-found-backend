<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$dbFile = __DIR__ . "/../database/lostfound.db";

try {
    $db = new PDO("sqlite:" . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //Supports JSON and Form Data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (empty($data)) {
        $data = $_POST;
    }

    $reportId = isset($data['report_id']) ? intval($data['report_id']) : null;
    $newStatus = isset($data['new_status']) ? trim($data['new_status']) : '';

    if ($reportId === null || $reportId === 0 || $newStatus === '') {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "report_id and new_status are required"]);
        exit;
    }

    $stmt = $db->prepare("UPDATE reports SET report_status = ? WHERE report_id = ?");
    $stmt->execute([$newStatus, $reportId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Status updated successfully"]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Report not found or no changes made"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>