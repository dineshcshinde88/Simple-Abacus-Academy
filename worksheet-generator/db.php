<?php
// Simple DB helper for worksheet generator
// Update these constants to match your local MySQL credentials.

const DB_HOST = 'localhost';
const DB_NAME = 'abacus_worksheets';
const DB_USER = 'root';
const DB_PASS = '';

function db_connect(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        die('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function db_query(mysqli $conn, string $sql, array $params = []): mysqli_stmt {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        die('Database prepare failed: ' . $conn->error);
    }
    if (!empty($params)) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }
        $stmt->bind_param($types, ...$values);
    }
    if (!$stmt->execute()) {
        http_response_code(500);
        die('Database execute failed: ' . $stmt->error);
    }
    return $stmt;
}

function db_init_schema(): void {
    $conn = db_connect();
    $sql = "CREATE TABLE IF NOT EXISTS worksheets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(100) NOT NULL,
        operation VARCHAR(20) NOT NULL,
        digits INT NOT NULL,
        total_questions INT NOT NULL,
        rows_count INT NOT NULL,
        level INT DEFAULT NULL,
        data JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($sql);
    $conn->close();
}

// Uncomment to auto-create table on first load if you want.
// db_init_schema();
?>
