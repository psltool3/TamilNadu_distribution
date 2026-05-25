<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Logger.php');


if(!SessionCheck()){
	return;
}

require('Header.php');

if(empty($_POST) || empty($_POST['uid'])){
    die("Something went wrong");
}

if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST["uid"])) {
    die("Invalid UID format");
}


$uid = $_POST["uid"];

$uid = mysqli_real_escape_string($con, $uid); // Sanitize input
$log_query = "SELECT user_id, message FROM user_message WHERE id='$uid'";
$log_result = mysqli_query($con, $log_query);

if (!$log_result) {
    die("Query failed: " . mysqli_error($con));
}

$row = $log_result->fetch_assoc();

if (empty($row)) {
    die("Something went wrong..");
}


// Extract values
$user_id = $row['user_id'];
$user_message = $row['message'];

if (empty($user_id)) {
    die("User ID is empty..");
}


$log_query = "select username  from login WHERE uid='$user_id'";
$log_result = mysqli_query($con,$log_query);
if ($log_result && $row = $log_result->fetch_assoc()) {
	$log_name =  $row['username'];
}

$query = "UPDATE user_message SET acknowledged='yes' WHERE id='$uid'";
mysqli_query($con,$query);
mysqli_close($con);

$filteredPost = $_POST;
unset($filteredPost['username'], $filteredPost['password']);
writeLog("User ->" ." Read Message ->". $_SESSION['district_user'] . "| Requested JSON -> " . $user_message. " | " . $log_name);


echo "<script>window.location.href = '../Message.php';</script>";


?>
<?php require('Fullui.php');  ?>