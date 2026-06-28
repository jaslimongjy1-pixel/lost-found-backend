<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once "connection.php";

function json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        "status" => "error",
        "message" => $message
    ]);
    exit;
}

$reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$userRole = strtolower(trim($_POST['user_role'] ?? ''));
$title = trim($_POST['report_title'] ?? '');
$type = trim($_POST['report_type'] ?? '');
$category = trim($_POST['report_category'] ?? '');
$location = trim($_POST['report_location'] ?? '');
$status = trim($_POST['report_status'] ?? 'Pending');
$description = trim($_POST['report_description'] ?? '');

if ($reportId === null || $reportId === false || $title === '' || $type === '' || $category === '' || $location === '' || $description === '') {
    json_error('Required fields are missing');
}

if ($userId === null || $userId === false) {
    json_error('User authentication is required', 401);
}

$uploadDir = __DIR__ . '/../uploads/reports/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

try {
    $stmt = $db->prepare("SELECT user_id, report_image FROM reports WHERE report_id = ? LIMIT 1");
    $stmt->execute([$reportId]);
    $existingReport = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingReport) {
        json_error('Report not found', 404);
    }

    $ownerId = $existingReport['user_id'] ?? 0;
    $isOwner = ($ownerId == $userId);
    $isAdmin = ($userRole == 'lecturer' || $userRole == 'admin');

    if (!$isOwner && !$isAdmin) {
        json_error('Forbidden: You are not the owner or an admin.', 403);
    }

    $imageFileName = (string) ($existingReport['report_image'] ?? '');
    $hasNewImage = isset($_FILES['image'])
        && isset($_FILES['image']['tmp_name'])
        && is_uploaded_file($_FILES['image']['tmp_name']);

    if ($hasNewImage) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExtensions, true)) {
            json_error('Invalid image type');
        }

        $imageFileName = 'report_' . $reportId . '.' . $ext;
        $targetPath = $uploadDir . $imageFileName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            json_error('Failed to upload image', 500);
        }

        $oldImage = (string) ($existingReport['report_image'] ?? '');
        if ($oldImage !== '' && $oldImage !== $imageFileName && file_exists($uploadDir . $oldImage)) {
            @unlink($uploadDir . $oldImage);
        }
    }

    $stmt = $db->prepare(
        "UPDATE reports
         SET report_type = ?, report_title = ?, report_description = ?, report_category = ?, report_location = ?, report_status = ?, report_image = ?
         WHERE report_id = ?"
    );

    $stmt->execute([$type, $title, $description, $category, $location, $status, $imageFileName, $reportId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Report updated successfully'
    ]);
} catch (Throwable $e) {
    json_error($e->getMessage(), 500);
}
?>