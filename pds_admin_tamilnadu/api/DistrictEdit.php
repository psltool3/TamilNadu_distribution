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

if(empty($_POST) || empty($_POST['username']) || empty($_POST['password'])){
	die("Something went wrong...");
}

function formatName($name) {
	if(preg_match('/[^a-zA-Z\s]/', $name)){
        echo "Error : Name contains invalid characters. Only letters and spaces are allowed.";
		exit();
    }
    $name = ucwords(strtolower($name));
    return trim($name);
}

function formatUID($name) {
    // Allow only letters and numbers (no spaces)
    if (preg_match('/[^a-zA-Z0-9]/', $name)) {
        echo "Error: Only letters and numbers are allowed.";
        exit();
    }
 
    return trim($name);
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

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
    $District = new District;

	$District->setName(formatName(str_replace("'","",$_POST['name'])));
	$District->setId(formatUID($_POST['uid']));

	$query = $District->update($District);
	$result = mysqli_query($con,$query);

	mysqli_close($con);

	if($result){
		$filteredPost = $_POST;
		unset($filteredPost['username'], $filteredPost['password']);
		writeLog("User ->" ." District Name Edit->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));
		echo "<script>window.location.href = '../District.php';</script>";
	}
	else{
	echo "Error : in update";
	}
}else{
	echo "Error : Password or Username is incorrect";
}

?>
<?php require('Fullui.php');  ?>