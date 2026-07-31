<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Auto-create table if not yet exists (runs fast after first call)
$conn->query("CREATE TABLE IF NOT EXISTS ejr_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    description TEXT DEFAULT '',
    price_from DECIMAL(10,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$result = $conn->query("SELECT id, name, category, description, price_from FROM ejr_services WHERE is_active = 1 ORDER BY category, name ASC");
$services = [];
while ($r = $result->fetch_assoc()) $services[] = $r;

// If no services in DB yet, return the hardcoded defaults so booking still works
if (empty($services)) {
    $defaults = [
        ['id'=>null,'name'=>'PMS','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'Change Oil','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'Auto Electrical Transmission','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'EFI','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'Carburator Services','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'Decarbonizer','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'Scanning Fault Codes','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'Paint Services','category'=>'Popular Services','description'=>'','price_from'=>0],
        ['id'=>null,'name'=>'Back to Zero (Anti-bacterial fogging)','category'=>'Popular Services','description'=>'','price_from'=>0],
    ];
    echo json_encode(['status'=>'success','services'=>$defaults,'source'=>'defaults']);
    exit;
}

echo json_encode(['status'=>'success','services'=>$services,'source'=>'database']);
