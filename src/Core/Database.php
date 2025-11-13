<?php
namespace Src\Core;

use mysqli;

class Database {
    private mysqli $conn;

    public function __construct(array $cfg) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
        $this->conn->set_charset($cfg['charset'] ?? 'utf8mb4');
    }

    public function conn(): mysqli {
        return $this->conn;
    }
}
