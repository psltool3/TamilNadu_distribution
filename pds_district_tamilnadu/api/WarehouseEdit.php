<?php

require('../util/Connection.php');
require('../structures/Warehouse.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');
$storage_original = isset($_POST['storage']) ? $_POST['storage'] : null;
$name_original = isset($_POST['name']) ? $_POST['name'] : null;
require('../util/Security.php');
if ($storage_original === '0' || $storage_original === 0) {
    $_POST['storage'] = '0';
}
if ($name_original !== null) {
    $_POST['name'] = $name_original;
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
	echo "User is logged in with different username and password";
	return;
}

if (strtolower($_SESSION['district_district']) !== strtolower($_POST['district'])) {
    die("Invalid request");
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);
$numrows = mysqli_num_rows($result);


if(!isValidCoordinate($_POST["latitude"],'latitude') or !isValidCoordinate($_POST["longitude"],'longitude')){
	echo "Error : Check Latitude and Longitude Value";
	exit();
}

if(!isStringNumber($_POST["storage"])){
	echo "Error : Check Storage Value";
	exit();
}

function validateId($id) {
    if (!preg_match('/^[a-zA-Z0-9]+$/', $id)) {
        die("Invalid ID: Only letters and numbers are allowed.");
    }
    return $id;  
}

if (!isset($_POST["latitude"]) || !is_numeric($_POST["latitude"]) || $_POST["latitude"] >= 40) {
	echo "Error : Latitude must be less than 40.";
	exit();
}

// Longitude check (must be more than 65)
if (!isset($_POST["longitude"]) || !is_numeric($_POST["longitude"]) || $_POST["longitude"] <= 65) {
	echo "Error : Longitude must be more than 65.";
	exit();
}

if (
    empty($_POST['district']) ||
    empty($_POST['latitude']) ||
    empty($_POST['longitude']) ||
    empty($_POST['name']) ||
    empty($_POST['id']) ||
    empty($_POST['type']) ||
    (!isset($_POST['storage']) || $_POST['storage'] === '') ||
    empty($_POST['warehousetype']) ||
    empty($_POST['uniqueid']) ||
    empty($_POST['active']) // also required
) {
    die("Error: All fields are required and must not be empty.");
}


$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
    
    $district = formatName($_POST["district"]);
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $name = formatName($_POST["name"]);
    $id = validateId($_POST["id"]);
    $type = $_POST["type"];
    $storage = $_POST["storage"];
    $warehousetype = $_POST["warehousetype"];
    $uniqueid = $_POST["uniqueid"];
    $active = $_POST["active"];

    $Warehouse = new Warehouse;
    $Warehouse->setUniqueid($uniqueid);
    $Warehouse->setDistrict($district);
    $Warehouse->setLatitude($latitude);
    $Warehouse->setLongitude($longitude);
    $Warehouse->setName($name);
    $Warehouse->setId($id);
    $Warehouse->setType($type);
    $Warehouse->setStorage($storage);
    $Warehouse->setWarehousetype($warehousetype);
    $Warehouse->setActive($active);

    $query = $Warehouse->update($Warehouse);

    mysqli_query($con, $query);

    mysqli_close($con);
	
	$filteredPost = $_POST;
	unset($filteredPost['username'], $filteredPost['password']);
	writeLog("district_user ->" ." Warehouse Edit ->". $_SESSION['district_user'] . "| Requested JSON -> " . json_encode($filteredPost));

    echo "<script>window.location.href = '../Warehouse.php';</script>";
} 
else{
    echo "Error : Password or Username is incorrect";
}

?>
<?php require('Fullui.php');  ?>