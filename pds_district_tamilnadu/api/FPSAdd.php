<?php

require('../util/Connection.php');
require('../structures/FPS.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
require('../util/Security.php');
require('../util/Logger.php');
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

if(empty($_POST) || empty($_POST['username']) || empty($_POST['password']) || empty($_POST['latitude']) || empty($_POST['longitude'])){
    die("Something went wrong");
}

if(empty($_POST['demand'])  || empty($_POST['demand_rice']) || empty($_POST['demand_frice'])){
    die("Check demand...");
}

require('Header.php');

function formatName($name) {
    $name = preg_replace('/[^a-zA-Z ]/', '', $name);
    $name = ucwords(strtolower($name));
    return trim($name);
}

function isValidCoordinate($value, $coordinateType) {
    // Check if the value is a number and not a string
    if (!is_numeric($value)) {
        return false;
    }
	
    // Convert the value to a float
    $coordinate = floatval($value);

    // Check if it's latitude or longitude and validate within the range
    switch ($coordinateType) {
        case 'latitude':
            return ($coordinate >= -90 && $coordinate <= 90);
        case 'longitude':
            return ($coordinate >= -180 && $coordinate <= 180);
        default:
            return false;
    }
}

function isStringNumber($stringValue) {
    return is_numeric($stringValue);
}

$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

if($_SESSION['district_user']!=$person->getUsername()){
	die("User is logged in with different username and password");
}

if (strtolower($_SESSION['district_district']) !== strtolower($_POST['district'])) {
    die("Invalid request");
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

if(!isValidCoordinate($_POST["latitude"],'latitude') or !isValidCoordinate($_POST["longitude"],'longitude')){
	echo "Error : Check Latitude and Longitude Value";
	exit();
}

if(!isStringNumber($_POST["demand"])){
	echo "Error : Check DemandWheat Value";
	exit();
}
if(!isStringNumber($_POST["demand_rice"])){
	echo "Error : Check DemandRice Value";
	exit();
}
if(!isStringNumber($_POST["demand_frice"])){
	echo "Error : Check DemandFRice Value";
	exit();
}

function validateId($id) {
    if (!preg_match('/^[a-zA-Z0-9]+$/', $id)) {
        die("Invalid ID: Only letters and numbers are allowed.");
    }
    return $id;  
}

if (!is_numeric($column[$latitude]) || $column[$latitude] >= 40) {
	echo "Error : Latitude must be less than 40. Given: " . $column[$latitude];
	echo "</br>";
	$redirect = 0;
}

// Longitude check (must be more than 65)
if (!is_numeric($column[$longitude]) || $column[$longitude] <= 65) {
	echo "Error : Longitude must be more than 65. Given: " . $column[$longitude];
	echo "</br>";
	$redirect = 0;
}

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
    $district = $_POST["district"];
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $name = $_POST["name"];
    $id = validateId($_POST["id"]);
    $type = $_POST["type"];
    $demand = $_POST["demand"];
    $demand_rice = $_POST["demand_rice"];
    $demand_frice = $_POST["demand_frice"];
    $uniqueid = uniqid("FPS_",);


    $FPS = new FPS;
    $FPS->setUniqueid(substr($uniqueid,0,15));
    $FPS->setDistrict($district);
    $FPS->setLatitude($latitude);
    $FPS->setLongitude($longitude);
    $FPS->setName($name);
    $FPS->setId($id);
    $FPS->setType($type);
    $FPS->setDemand($demand);
    $FPS->setDemandrice($demand_rice);
    $FPS->setDemandfrice($demand_frice);
    $FPS->setActive("1");

    $query_insert_check = $FPS->checkInsert($FPS);
    $query_insert_result = mysqli_query($con, $query_insert_check);
    $numrows_insert = mysqli_num_rows($query_insert_result);
    if($numrows_insert==0){
        $query = $FPS->insert($FPS);
        mysqli_query($con, $query);
        mysqli_close($con);
		
		$filteredPost = $_POST;
		unset($filteredPost['username'], $filteredPost['password']);
		writeLog("district_user ->" ." FPS added ->". $_SESSION['district_user'] . "| Requested JSON -> " . json_encode($filteredPost));
        echo "<script>window.location.href = '../FPS.php';</script>";
    }
    else{
        echo "Error : in Insertion as FPS id already exist";
    }
} 
else{
    echo "Error : Password or Username is incorrect";
}

?>
<?php require('Fullui.php');  ?>
