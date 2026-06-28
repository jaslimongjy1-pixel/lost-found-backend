<?php
require_once "connection.php";

// Print the file path connected to

$stmt = $db->query("PRAGMA database_list;");
$result = $stmt->fetchAll();
print_r($result);

// List all columns in the users table
$stmt = $db->query("PRAGMA table_info(users);");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nColumns in 'users' table:\n";
foreach ($columns as $col) {
    echo "- " . $col['name'] . "\n";
}
?>