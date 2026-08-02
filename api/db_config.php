// Load Environment Variables
$host = getenv('DB_HOST');
$port = (int)getenv('DB_PORT');
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');

if (
    empty($host) ||
    empty($port) ||
    empty($db) ||
    empty($user) ||
    empty($pass)
) {
    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        "status"=>"error",
        "message"=>"Database environment variables are missing."
    ]);
    exit;
}

try {

    $conn = mysqli_init();

    mysqli_ssl_set(
        $conn,
        null,
        null,
        __DIR__ . '/../certificates/isrgrootx1.pem',
        null,
        null
    );

    mysqli_real_connect(
        $conn,
        $host,
        $user,
        $pass,
        $db,
        $port,
        null,
        MYSQLI_CLIENT_SSL
    );

    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");

} catch (Throwable $e) {

    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        "status"=>"error",
        "message"=>$e->getMessage()
    ]);

    exit;
}