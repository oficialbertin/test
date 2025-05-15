<?php
require_once 'util.php';

try {
    // Test database connection
    $conn = new PDO("mysql:host=" . Util::$host . ";dbname=" . Util::$db, Util::$user, Util::$pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n";

    // Check if tables exist
    $tables = ['citizens', 'umuganda_events', 'attendance'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists.\n";
            
            // Show table structure
            $stmt = $conn->query("DESCRIBE $table");
            echo "Structure of '$table':\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        } else {
            echo "Table '$table' does not exist!\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // If database doesn't exist, try to create it
    if ($e->getCode() == 1049) { // Error code for unknown database
        try {
            $conn = new PDO("mysql:host=" . Util::$host, Util::$user, Util::$pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database
            $conn->exec("CREATE DATABASE " . Util::$db);
            echo "Database created successfully!\n";
            
            // Select the database
            $conn->exec("USE " . Util::$db);
            
            // Create tables
            $conn->exec("
                CREATE TABLE citizens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100),
                    phone VARCHAR(15),
                    national_id VARCHAR(16) UNIQUE
                )
            ");
            
            $conn->exec("
                CREATE TABLE umuganda_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(100),
                    location VARCHAR(100),
                    event_date DATE
                )
            ");
            
            $conn->exec("
                CREATE TABLE attendance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    citizen_id INT,
                    event_id INT,
                    attended BOOLEAN DEFAULT 0,
                    FOREIGN KEY (citizen_id) REFERENCES citizens(id),
                    FOREIGN KEY (event_id) REFERENCES umuganda_events(id)
                )
            ");
            
            echo "Tables created successfully!\n";
        } catch (PDOException $e) {
            echo "Error creating database/tables: " . $e->getMessage() . "\n";
        }
    }
} 