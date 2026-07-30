<?php
require('../util/Connection.php');
require('../structures/Login.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$username = $_POST['username'] ?? '';
$oldpassword = $_POST['oldpassword'] ?? '';
$newpassword = $_POST['newpassword'] ?? '';
$confirmpassword = $_POST['confirmpassword'] ?? '';

if(empty($username) || empty($oldpassword) || empty($newpassword) || empty($confirmpassword)){
	echo "Error: All fields are required.";
	exit;
}

if($newpassword !== $confirmpassword){
	echo "Error: Both Passwords don't match.";
	exit;
}

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
	if (password_verify($oldpassword, $row['password']) || $oldpassword === $row['password']) {
		$hashedNewPassword = password_hash($newpassword, PASSWORD_DEFAULT);
		$queryUpdate = "UPDATE login SET password='$hashedNewPassword' WHERE username='$username_safe'";
		mysqli_query($con, $queryUpdate);

		mysqli_close($con);
		echo "<script>alert('Password updated successfully!'); window.location.href = '../Login.html';</script>";
	} else {
		echo "Error: Username or Old Password is incorrect.";
	}
}
?>