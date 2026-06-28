<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once "connection.php";

try {
    // Get Pagination and Filter Parameters
    $page = max(1, (int) ($_GET["page"] ?? 1));
    $limit = max(1, min(100, (int) ($_GET["limit"] ?? 10)));
    $search = trim($_GET["search"] ?? "");
    $category = trim($_GET["category"] ?? "All"); // e.g., Lost or Found

    $whereClauses = [];
    $params = [];

    //Build Search Query
    if ($search !== "") {
        $whereClauses[] = "(report_title LIKE :search OR report_description LIKE :search OR report_location LIKE :search)";
        $params[":search"] = "%" . $search . "%";
    }

    //Build Category Filter (Lost/Found/All)
    if ($category !== "" && strcasecmp($category, "All") !== 0) {
        $whereClauses[] = "report_category = :category";
        $params[":category"] = $category;
    }

    $whereSql = empty($whereClauses) ? "" : " WHERE " . implode(" AND ", $whereClauses);

    //Get Total Count (Required for Frontend Pagination)
    $countStmt = $db->prepare("SELECT COUNT(*) FROM reports" . $whereSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalItems = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalItems / $limit));

    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $limit;

    //Fetch Data
    $stmt = $db->prepare("
        SELECT report_id, user_id, report_type, report_title, report_description, 
               report_category, report_location, report_status, report_image, report_date
        FROM reports
        $whereSql
        ORDER BY report_id DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "reports" => $reports,
        "current_page" => $page,
        "per_page" => $limit,
        "total_items" => $totalItems,
        "total_pages" => $totalPages
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>