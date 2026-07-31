<?php
header("Content-Type: application/json");
require_once 'db_config.php';
require_once 'rate_limit.php';
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
        $userId = $decoded->id ?? $decoded->uid;
        ejr_rate_limit($conn, "add_appointment", ejr_get_ip() . "|" . $userId, 10, 300);

        $input = json_decode(file_get_contents("php://input"), true);
        
        // Combine services into a string
        $services = implode(", ", $input['services']);
        // Combine vehicle details into a single readable info string
        $v = $input['vehicle'];
        $v_info = $v['brand'] . " " . $v['model'] . " " . $v['year'] . " (" . $v['plate'] . ")";
        
        $date = $input['date'];
        $notes = $input['notes'] ?? '';

        $query = "INSERT INTO appointments (user_id, service_type, vehicle_info, appointment_date, notes, status) 
                  VALUES (?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issss", $userId, $services, $v_info, $date, $notes);
        
        if($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "SQL Error"]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    }
}