<?php
require_once "connection.php";

// This will print the file path you are actually connected to
// (This assumes your $db variable is defined in connection.php)
$stmt = $db->query("PRAGMA database_list;");
$result = $stmt->fetchAll();
print_r($result);

// This will list all columns in the users table
$stmt = $db->query("PRAGMA table_info(users);");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nColumns in 'users' table:\n";
foreach ($columns as $col) {
    echo "- " . $col['name'] . "\n";
}
?>