<?php

header("Content-Type: application/json");

require_once "db_config.php";


// ============================================================
// GET AVAILABLE MECHANICS
// ============================================================
//
// Only mechanics marked as "available" by the admin
// are returned to customers.
//
// unavailable  -> hidden
// on_break     -> hidden
// on_duty      -> hidden
// available    -> shown
// ============================================================


$sql = "
    SELECT
        id,
        name,
        specialty,
        status
    FROM mechanics
    WHERE status = 'available'
    ORDER BY name ASC
";


$result = $conn->query($sql);


if (!$result) {

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => "Unable to load mechanics."
    ]);

    exit;

}


$mechanics = [];


while (
    $row = $result->fetch_assoc()
) {

    $mechanics[] = [

        "id" =>
            (int) $row["id"],

        "name" =>
            $row["name"],

        "specialty" =>
            $row["specialty"],

        "status" =>
            $row["status"]

    ];

}


echo json_encode([

    "status" =>
        "success",

    "mechanics" =>
        $mechanics

]);

?>