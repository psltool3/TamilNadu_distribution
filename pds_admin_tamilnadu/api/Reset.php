<?php
require('../util/Connection.php');
require('../structures/Login.php');
require('../util/Encryption.php');

$nonceValue = 'nonce_value';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$username = $_POST['username'] ?? '';
$oldpassword_raw = $_POST['oldpassword'] ?? '';
$newpassword_raw = $_POST['newpassword'] ?? '';
$confirmpassword_raw = $_POST['confirmpassword'] ?? '';

if(empty($username) || empty($oldpassword_raw) || empty($newpassword_raw) || empty($confirmpassword_raw)){
	echo "Error: All fields are required.";
	exit;
}

if($newpassword_raw !== $confirmpassword_raw){
	echo "Error: Both Passwords don't match.";
	exit;
}

$Encryption = new Encryption();
$oldpassword = $Encryption->decrypt($oldpassword_raw, $nonceValue);
$newpassword = $Encryption->decrypt($newpassword_raw, $nonceValue);

if(empty($oldpassword)){ $oldpassword = $oldpassword_raw; }
if(empty($newpassword)){ $newpassword = $newpassword_raw; }

$person = new Login;
$person->setUsername($username);
$person->setPassword($oldpassword);

$username_safe = mysqli_real_escape_string($con, $person->getUsername());
$query = "SELECT * FROM login WHERE username='$username_safe'";
$result = mysqli_query($con, $query);

if (!$result || mysqli_num_rows($result) == 0) {
	echo "Error: Username or Old Password is incorrect.";
} else {
	$row = mysqli_fetch_assoc($result);
	$dbPassword = $row['password'];
	
	if (password_verify($oldpassword, $dbPassword) || $oldpassword === $dbPassword || md5($oldpassword) === $dbPassword) {
		$hashedNewPassword = password_hash($newpassword, PASSWORD_DEFAULT);
		$queryUpdate = "UPDATE login SET password='$hashedNewPassword' WHERE username='$username_safe'";
		mysqli_query($con, $queryUpdate);

		mysqli_close($con);
		echo "<script>alert('Password updated successfully!'); window.location.href = '../AdminLogin.html';</script>";
	} else {
		echo "Error: Username or Old Password is incorrect.";
	}
}
?>