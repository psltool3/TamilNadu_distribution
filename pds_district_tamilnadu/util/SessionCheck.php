<?php
require('Connection.php');

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$ip_address = "";
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip_address = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip_address = $_SERVER['REMOTE_ADDR'];
}

// Session Hijack Protection: bind to IP and User-Agent
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

if (!isset($_SESSION['USER_IP']) || !isset($_SESSION['USER_AGENT'])) {
    $_SESSION['USER_IP'] = $user_ip;
    $_SESSION['USER_AGENT'] = $user_agent;
} else {
    if ($_SESSION['USER_IP'] !== $user_ip || $_SESSION['USER_AGENT'] !== $user_agent) {
        session_unset();
        session_destroy();
        header("Location: Login.html?error=session_hijacked");
        exit();
    }
}


$timeout_duration = 20000;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: Login.html?error=session_timeout");
    exit();
}
$_SESSION['last_activity'] = time();


// User & token verification
if (isset($_SESSION['district_user'])) {
    $user = $_SESSION['district_user'];
    $token = $_SESSION['district_token'];
    $query = "SELECT * FROM login WHERE username='$user' AND token='$token'";
    $result = mysqli_query($con, $query);
    $numrows = mysqli_num_rows($result);

    if ($numrows == 0) {
        header("Location: Login.html");
        exit();
    }

    $currentLoginTime = date("Y-m-d H:i:s");
    $queryUpdate = "UPDATE login SET lastlogin='$currentLoginTime' WHERE username='$user'";
    mysqli_query($con, $queryUpdate);
} else {
    header("Location: Login.html");
    exit();
}
?>
