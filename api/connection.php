<?php
// Pointing to the 'database' folder 
$db_file = __DIR__ . "/../database/lostfound.db";

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec('PRAGMA busy_timeout = 5000;');

    //Automatically create the 'users' table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_name TEXT NOT NULL,
            user_email TEXT NOT NULL UNIQUE,
            user_password TEXT NOT NULL,
            user_phone TEXT,
            user_date DATETIME DEFAULT (datetime('now', 'localtime')),
            user_role TEXT NOT NULL
        )
    ");

    //Automatically create the 'reports' table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS reports (
            report_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            report_type TEXT NOT NULL,
            report_title TEXT NOT NULL,
            report_description TEXT NOT NULL,
            report_category TEXT NOT NULL,
            report_location TEXT NOT NULL,
            report_status TEXT NOT NULL,
            report_image TEXT NOT NULL,
            report_date DATETIME DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY(user_id) REFERENCES users(user_id)
        )
    ");

} catch (PDOException $e) {
   
    die(json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]));
}
?>