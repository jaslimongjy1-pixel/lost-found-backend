<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Write errors to a log file 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once "connection.php";


$userId = trim($_POST['user_id'] ?? '');
$title = trim($_POST['report_title'] ?? '');
$type = trim($_POST['report_type'] ?? '');
$category = trim($_POST['report_category'] ?? '');
$location = trim($_POST['report_location'] ?? '');
$description = trim($_POST['report_description'] ?? '');
$report_date = date('Y-m-d H:i:s');

//Validate input and file
if (empty($userId) || empty($title) || empty($type) || empty($category) || empty($location) || empty($description) || !isset($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields or image"]);
    exit;
}

//Prepare Directory
$uploadDir = __DIR__ . "/../uploads/reports/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

try {
    //Insert into database
    $stmt = $db->prepare("
        INSERT INTO reports (user_id, report_type, report_title, report_description, report_category, report_location, report_status, report_image, report_date)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)
    ");
    $stmt->execute([$userId, $type, $title, $description, $category, $location, 'temp.jpg', $report_date]);

    $reportId = $db->lastInsertId();

    //Handle File Upload
    $fileName = "report_" . $reportId . ".jpg";
    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
        // Update database with real filename
        $stmt = $db->prepare("UPDATE reports SET report_image = ? WHERE report_id = ?");
        $stmt->execute([$fileName, $reportId]);
        
        echo json_encode(["status" => "success", "message" => "Report submitted successfully"]);
    } else {
        throw new Exception("Failed to move uploaded file");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>