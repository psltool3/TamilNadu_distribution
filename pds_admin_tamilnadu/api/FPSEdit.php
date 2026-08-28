<?php

require('../util/Connection.php');
require('../structures/FPS.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
$demand_original = isset($_POST['demand']) ? $_POST['demand'] : null;
$demand_rice_original = isset($_POST['demand_rice']) ? $_POST['demand_rice'] : null;
$demand_frice_original = isset($_POST['demand_frice']) ? $_POST['demand_frice'] : null;
$name_original = isset($_POST['name']) ? $_POST['name'] : null;
require('../util/Security.php');
if ($name_original !== null) {
    $_POST['name'] = $name_original;
}
if ($demand_original === '0' || $demand_original === 0) {
    $_POST['demand'] = '0';
}
if ($demand_rice_original === '0' || $demand_rice_original === 0) {
    $_POST['demand_rice'] = '0';
}
if ($demand_frice_original === '0' || $demand_frice_original === 0) {
    $_POST['demand_frice'] = '0';
}
require ('../util/Encryption.php');
require('../util/Logger.php');
$nonceValue = 'nonce_value';

if(!SessionCheck()){
	return;
}

require('Header.php');

if(empty($_POST) || empty($_POST["username"]) || empty($_POST["password"])){
	die("Something went wrong...");
}


function formatName($name) {
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

if(!isValidCoordinate($_POST["latitude"],'latitude') or !isValidCoordinate($_POST["longitude"],'longitude')){
	echo "Error : Check Latitude and Longitude Value";
	exit();
}

if ($_POST['demand_rice'] === false)
{
    $_POST['demand_rice'] = 0;
}
if ($_POST['demand'] === false)
{
    $_POST['demand'] = 0;
}
if ($_POST['demand_frice'] === false)
{
    $_POST['demand_frice'] = 0;
}


if(!isStringNumber($_POST["demand"]) || floatval($_POST["demand"]) < 0){
	echo "Error : Check Entitlement Wheat Value: value cannot be negative";
	exit();
}
if(!isStringNumber($_POST["demand_rice"]) || floatval($_POST["demand_rice"]) < 0){
	echo "Error : Check Entitlement Rice Value: value cannot be negative";
	exit();
}
if(!isStringNumber($_POST["demand_frice"]) || floatval($_POST["demand_frice"]) < 0){
	echo "Error : Check Entitlement FRice Value: value cannot be negative";
	exit();
}

function validateId($id) {
    if (!preg_match('/^[a-zA-Z0-9]+$/', $id)) {
        die("Invalid ID: Only letters and numbers are allowed.");
    }
    return $id;  
}

if (!isset($_POST["latitude"]) || !is_numeric($_POST["latitude"]) || $_POST["latitude"] >= 40) {
    echo "Check Latitude: value must be less than 40";
    exit();
}

// Longitude must be greater than 65
if (!isset($_POST["longitude"]) || !is_numeric($_POST["longitude"]) || $_POST["longitude"] <= 65) {
    echo "Check Longitude: value must be greater than 65";
    exit();
}

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){

    $district = mysqli_real_escape_string($con, formatName($_POST["district"]));
	$latitude = mysqli_real_escape_string($con, $_POST["latitude"]);
	$longitude = mysqli_real_escape_string($con, $_POST["longitude"]);
	$name = mysqli_real_escape_string($con, formatName($_POST["name"]));
	$id = mysqli_real_escape_string($con, validateId($_POST["id"]));
	$type = mysqli_real_escape_string($con, $_POST["type"]);
	$demand = mysqli_real_escape_string($con, $_POST["demand"]);
	$demand_rice = mysqli_real_escape_string($con, $_POST["demand_rice"]);
	$demand_frice = mysqli_real_escape_string($con, $_POST["demand_frice"]);
	$uniqueid = mysqli_real_escape_string($con, $_POST["uniqueid"]);
	$active = mysqli_real_escape_string($con, $_POST["active"]);

	$FPS = new FPS;
	$FPS->setUniqueid($uniqueid);
	$FPS->setDistrict($district);
	$FPS->setLatitude($latitude);
	$FPS->setLongitude($longitude);
	$FPS->setName($name);
	$FPS->setId($id);
	$FPS->setType($type);
	$FPS->setDemand($demand);
	$FPS->setDemandRice($demand_rice);
	$FPS->setDemandFRice($demand_frice);
	$FPS->setActive($active);

	$query_check = $FPS->checkInsert($FPS);
	$query_result = mysqli_query($con, $query_check);
	$numrows = mysqli_num_rows($query_result);
	if($numrows!=0){
		$row = mysqli_fetch_assoc($query_result);
		$uniqueid_check = $row["uniqueid"];
		if($uniqueid!=$uniqueid_check){
			echo "Error : in updating data as FPS id already exist ID: ".$id;
			echo "</br>";
			exit();
		}
	}

	$query = $FPS->update($FPS);
	mysqli_query($con, $query);

	mysqli_close($con);
	
	$filteredPost = $_POST;
	unset($filteredPost['username'], $filteredPost['password']);
	writeLog("User ->" ." FPS Data Edit->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));

	echo "<script>window.location.href = '../FPS.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}


?>
<?php require('Fullui.php');  ?>