<?php
// Ensure this path is correct based on where db_config.php is located
require_once 'db_config.php';

// Error display disabled in production — errors are logged server-side only
error_reporting(0);
ini_set('display_errors', 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified | EJR Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .success-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 90%; }
        .success-icon { font-size: 60px; color: #28a745; margin-bottom: 20px; }
        .btn-garage { background-color: #e62429; color: white; border-radius: 8px; padding: 12px 25px; text-decoration: none; font-weight: bold; display: inline-block; transition: 0.3s; }
        .btn-garage:hover { background-color: #b31b1f; color: white; }
    </style>
</head>
<body>

<div class="success-card">
    <?php
    if (isset($_GET['token'])) {
        $token = mysqli_real_escape_string($conn, $_GET['token']);
        $query = "SELECT id FROM users WHERE verification_token='$token' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_query($conn, "UPDATE users SET is_verified=1, verification_token=NULL WHERE verification_token='$token'");
            echo '<div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>';
            echo '<h2 class="fw-bold mb-3">Verified!</h2>';
            echo '<p class="text-muted mb-4">Your email has been successfully verified. You can now access your EJR Garage account.</p>';
            echo '<a href="../login.php" class="btn-garage">Login to Your Account</a>';
        } else {
            echo '<div class="text-danger mb-4" style="font-size: 60px;"><i class="fa-solid fa-circle-xmark"></i></div>';
            echo '<h2 class="fw-bold mb-3">Invalid Link</h2>';
            echo '<p class="text-muted mb-4">This link is either expired or has already been used.</p>';
            echo '<a href="../login.php" class="btn-secondary btn p-2 px-4">Go Back</a>';
        }
    } else {
        echo '<h2>Missing Token</h2>';
    }
    ?>
</div>

</body>
</html>