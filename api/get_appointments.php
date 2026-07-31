<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined("JWT_SECRET")) require_once __DIR__ . "/env.php";
$secret_key = JWT_SECRET; 
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$authHeader = $headers['authorization'] ?? '';

if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
    try {
        $decoded = JWT::decode($matches[1], new Key($secret_key, 'HS256'));
        
        // Fixed: Using 'uid' to match your login.php payload
        $userId = $decoded->uid; 

        // Join with vehicles so your UI shows the car name instead of "Vehicle Details Pending"
        $query = "SELECT a.*, v.brand, v.model 
                  FROM appointments a 
                  LEFT JOIN vehicles v ON a.vehicle_id = v.id 
                  WHERE a.user_id = ? 
                  ORDER BY a.appointment_date DESC";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }

        echo json_encode(["status" => "success", "appointments" => $appointments]);

    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized: " . $e->getMessage()]);
    }
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token missing"]);
}