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

        // Join vehicles (for the car name) and technicians (for the
        // assigned mechanic's name), same JOIN pattern as
        // api/admin/get_full_site_data.php uses for consistency.
        $query = "SELECT a.*,
                         v.brand, v.model, v.plate_number,
                         CONCAT_WS(' ', v.brand, v.model,
                             CASE WHEN v.plate_number IS NOT NULL THEN CONCAT('(', v.plate_number, ')') ELSE NULL END
                         ) AS vehicle_info,
                         t.full_name AS technician_name,
                         t.is_available AS technician_available
                  FROM appointments a
                  LEFT JOIN vehicles v ON a.vehicle_id = v.id
                  LEFT JOIN technicians t ON a.technician_id = t.id
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