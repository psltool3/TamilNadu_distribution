<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/Security.php');
require('../util/Encryption.php');
$nonceValue = 'nonce_value';
require('../util/SessionFunction.php');

if (!SessionCheck()) {
    return;
}

if (empty($_POST) || empty($_POST["oldpassword"]) || empty($_POST["newpassword"]) || empty($_POST["confirmpassword"]) || empty($_POST['username'])) {
    die("Something went wrong...");
}

$person = new Login;
$Encryption = new Encryption();

$username = $_POST["username"];
$person->setUsername($username);
$person->setPassword($Encryption->decrypt($_POST["oldpassword"], $nonceValue));
$newpassword = $Encryption->decrypt($_POST["newpassword"], $nonceValue);
$confirmpassword = $Encryption->decrypt($_POST["confirmpassword"], $nonceValue);

if ($newpassword == "" || $confirmpassword == "") {
    echo "Error: Password is Empty";
    return;
}

if ($newpassword != $confirmpassword) {
    echo "Error: Both Passwords don't match";
    return;
}

$pattern = '/^(?=.*[A-Z])(?=.*[\W_]).{8,}$/';
if (!preg_match($pattern, $newpassword)) {
    echo "Error: Password must be at least 8 characters long, contain at least one uppercase letter, and one special character.";
    return;
}

// Get current hashed password
$query = "SELECT password FROM login WHERE username='" . mysqli_real_escape_string($con, $username) . "'";
$result = mysqli_query($con, $query);

if (!$result) {
    echo "Error: " . mysqli_error($con);
    exit;
}

$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "Error: User not found";
    exit;
}

$dbHashedPassword = $row['password'];


if (!password_verify($person->getPassword(), $dbHashedPassword)) {
    echo "Error: Old password is incorrect";
    exit;
}


if (password_verify($newpassword, $dbHashedPassword)) {
    echo "Error: New password must be different from the old password.";
    exit;
}


$historyQuery = "SELECT old_password_hash FROM password_history WHERE username='" . mysqli_real_escape_string($con, $username) . "' ORDER BY changed_at DESC LIMIT 5";
$historyResult = mysqli_query($con, $historyQuery);

if (!$historyResult) {
    echo "Error checking password history: " . mysqli_error($con);
    exit;
}

while ($historyRow = mysqli_fetch_assoc($historyResult)) {
    if (password_verify($newpassword, $historyRow['old_password_hash'])) {
        echo "Error: Please try using different Password.";
        exit;
    }
}


$insertHistory = "INSERT INTO password_history (username, old_password_hash) VALUES ('" . mysqli_real_escape_string($con, $username) . "', '$dbHashedPassword')";
if (!mysqli_query($con, $insertHistory)) {
    echo "Error saving password history: " . mysqli_error($con);
    exit;
}


$newhashedPassword = password_hash($newpassword, PASSWORD_DEFAULT);
$updateQuery = "UPDATE login SET password='$newhashedPassword' WHERE username='" . mysqli_real_escape_string($con, $username) . "'";
if (mysqli_query($con, $updateQuery)) {
    echo "Password updated successfully.";
} else {
    echo "Error updating password: " . mysqli_error($con);
}

mysqli_close($con);

session_unset();
session_destroy();

echo "<script>window.location.href = '../AdminLogin.html';</script>";
?>
<?php require('Fullui.php'); ?>
