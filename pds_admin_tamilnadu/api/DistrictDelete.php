<?php

require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Security.php');
require ('../util/Encryption.php');
require('../util/Logger.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

require('Header.php');

if(empty($_POST) || empty($_SESSION) || empty($_POST['username']) || empty($_POST['password'])){
    die("Something went wrong...");
}


$person = new Login;
$person->setUsername($_POST["username"]);

$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));


if($_SESSION['user']!=$person->getUsername()){
	echo "User is logged in with different username and password";
	return;
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);
$numrows = mysqli_num_rows($result);

if($numrows == 0){
	echo "Error : Password or Username is incorrect";
	return;
}


$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
	$District = new District;
	$District->setId($_POST['uid']);

	$query = $District->delete($District);
	
	$log_query = $District->logname($District);
	$log_result = mysqli_query($con,$log_query);
	if ($log_result && $row = $log_result->fetch_assoc()) {
		$log_name =  $row['name'];
	}

	mysqli_query($con,$query);
	mysqli_close($con);

	echo "<script>window.location.href = '../District.php';</script>";
}else{
	echo "Password or Username is incorrect" ;
}

?>
<?php require('Fullui.php');  ?>