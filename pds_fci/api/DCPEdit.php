<?php

require('../util/Connection.php');
require('../structures/DCP.php');
require('../structures/Login.php');
require('../util/SessionFunction.php');

$password_original = isset($_POST['password']) ? $_POST['password'] : null;
$demand_original = isset($_POST['demand']) ? $_POST['demand'] : null;
$demand_rice_original = isset($_POST['demand_rice']) ? $_POST['demand_rice'] : null;
$demand_frice_original = isset($_POST['demand_frice']) ? $_POST['demand_frice'] : null;

require('../util/Security.php');

if ($password_original !== null) {
    $_POST['password'] = $password_original;
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

function renderError($message) {
    echo "<div style='padding: 20px;'><div class='alert alert-danger' role='alert' style='font-size:16px;'><strong>Error:</strong> " . htmlspecialchars($message) . "</div></div>";
    require('Fullui.php');
    exit();
}

if(empty($_POST) || empty($_POST['username']) || empty($_POST['password'])){
    renderError("Invalid request: No form data submitted.");
}

function formatName($name) {
	$name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name);
    $name = ucwords(strtolower($name));
    return trim($name);
}

function isValidCoordinate($value, $coordinateType) {
    if (!is_numeric($value)) {
        return false;
    }
    $coordinate = floatval($value);
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

if($_SESSION['user'] != $person->getUsername()){
	renderError("User is logged in with different username and password");
}

$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

if(!$row){
    renderError("Password or Username is incorrect");
}

if(!isValidCoordinate($_POST["latitude"],'latitude') || !isValidCoordinate($_POST["longitude"],'longitude')){
	renderError("Check Latitude and Longitude Value");
}

if(!isStringNumber($_POST["demand"])){
	renderError("Check Offset Rice Value");
}
if(!isStringNumber($_POST["demand_rice"])){
	renderError("Check Offset Wheat Value");
}
if(!isStringNumber($_POST["demand_frice"])){
	renderError("Check Offset FRice Value");
}

if (!isset($_POST["id"]) || !preg_match('/^[A-Za-z0-9]+$/', $_POST["id"])) {
    renderError("Check FCI ID value (only letters and numbers allowed, no spaces or special characters)");
}
if (!isset($_POST["latitude"]) || !is_numeric($_POST["latitude"]) || $_POST["latitude"] >= 40) {
    renderError("Check Latitude: value must be less than 40");
}
if (!isset($_POST["longitude"]) || !is_numeric($_POST["longitude"]) || $_POST["longitude"] <= 65) {
    renderError("Check Longitude: value must be greater than 65");
}

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
	$district = strtoupper(trim($_POST["district"]));
	$latitude = $_POST["latitude"];
	$longitude = $_POST["longitude"];
	$name = formatName($_POST["name"]);
	$id = $_POST["id"];
	$type = $_POST["type"];
	$demand = $_POST["demand"];
	$demand_rice = $_POST["demand_rice"];
	$demand_frice = $_POST["demand_frice"];
	$uniqueid = $_POST["uniqueid"];
	$active = $_POST["active"];

	$DCP = new DCP;
	$DCP->setUniqueid($uniqueid);
	$DCP->setDistrict($district);
	$DCP->setLatitude($latitude);
	$DCP->setLongitude($longitude);
	$DCP->setName($name);
	$DCP->setId($id);
	$DCP->setType($type);
	$DCP->setDemand($demand);
	$DCP->setDemandrice($demand_rice);
	$DCP->setDemandfrice($demand_frice);
	$DCP->setActive($active);

	$query_check = $DCP->checkInsert($DCP);
	$query_result = mysqli_query($con, $query_check);
	$numrows = mysqli_num_rows($query_result);
	if($numrows != 0){
		$row = mysqli_fetch_assoc($query_result);
		$uniqueid_check = $row["uniqueid"];
		if($uniqueid != $uniqueid_check){
			renderError("Update failed: DCP with ID ".$id." already exists");
		}
	}

	$query = $DCP->update($DCP);
	mysqli_query($con, $query);

	mysqli_close($con);
	
	$filteredPost = $_POST;
	unset($filteredPost['username'], $filteredPost['password']);
	writeLog("User ->" ." DCP Edit ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));

	echo "<script>window.location.href = '../DCP.php';</script>";
} 
else{
    renderError("Password or Username is incorrect");
}

?>
<?php require('Fullui.php'); ?>