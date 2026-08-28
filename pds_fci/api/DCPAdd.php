<?php

require('../util/Connection.php');
require('../structures/DCP.php');
require('../structures/Login.php');
require('../util/SessionFunction.php');

$password_original = isset($_POST['password']) ? $_POST['password'] : null;
$demand_original = isset($_POST['demand']) ? $_POST['demand'] : null;
$demand_rice_original = isset($_POST['demand_rice']) ? $_POST['demand_rice'] : null;
$demand_frice_original = isset($_POST['demand_frice']) ? $_POST['demand_frice'] : null;
$name_original = isset($_POST['name']) ? $_POST['name'] : null;

require('../util/Security.php');

if ($password_original !== null) {
    $_POST['password'] = $password_original;
}
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
            return ($coordinate > 0 && $coordinate < 40);
        case 'longitude':
            return ($coordinate > 65 && $coordinate < 100);
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

if (!is_numeric($_POST['demand']) || floatval($_POST['demand']) < 0) {
	renderError("Check Offered Wheat Value: value cannot be negative");
}

if (!is_numeric($_POST['demand_rice']) || floatval($_POST['demand_rice']) < 0) {
	renderError("Check Offered Rice Value: value cannot be negative");
}

if (!is_numeric($_POST['demand_frice']) || floatval($_POST['demand_frice']) < 0) {
	renderError("Check Offered FRice Value: value cannot be negative");
}

if (!isset($_POST["id"]) || !preg_match('/^[A-Za-z0-9]+$/', $_POST["id"])) {
    renderError("Check FCI ID value (only letters and numbers allowed, no spaces or special characters)");
}

if (!isset($_POST["latitude"]) || !is_numeric($_POST["latitude"]) || $_POST["latitude"] <= 0 || $_POST["latitude"] >= 40) {
    renderError("Check Latitude: value must be greater than 0 and less than 40");
}

if (!isset($_POST["longitude"]) || !is_numeric($_POST["longitude"]) || $_POST["longitude"] <= 65 || $_POST["longitude"] >= 100) {
    renderError("Check Longitude: value must be greater than 65 and less than 100");
}

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
    $district = $_POST["district"];
    $latitude = $_POST["latitude"];
    $longitude = $_POST["longitude"];
    $name = $_POST["name"];
    $id = $_POST["id"];
    $type = $_POST["type"];
    $demand = $_POST["demand"];
    $demand_rice = $_POST["demand_rice"];
    $demand_frice = $_POST["demand_frice"];
    $uniqueid = uniqid("DCP_",);

    $DCP = new DCP;
    $DCP->setUniqueid(substr($uniqueid,0,15));
    $DCP->setDistrict(strtoupper(trim($district)));
    $DCP->setLatitude($latitude);
    $DCP->setLongitude($longitude);
    $DCP->setName($name);
    $DCP->setId($id);
    $DCP->setType($type);
    $DCP->setDemand($demand);
    $DCP->setDemandrice($demand_rice);
    $DCP->setDemandfrice($demand_frice);
    $DCP->setActive("1");

    $query_insert_check = $DCP->checkInsert($DCP);
    $query_insert_result = mysqli_query($con, $query_insert_check);
    $numrows_insert = mysqli_num_rows($query_insert_result);
    if($numrows_insert == 0){
        $query = $DCP->insert($DCP);
        mysqli_query($con, $query);
        mysqli_close($con);
        $filteredPost = $_POST;
        unset($filteredPost['username'], $filteredPost['password']);
        writeLog("User ->" ." DCP added ->". $_SESSION['user'] . "| Requested JSON -> " . json_encode($filteredPost));
        echo "<script>window.location.href = '../DCP.php';</script>";
    }
    else{
        renderError("Insertion failed: DCP with ID ".$id." already exists");
    }
} 
else{
    renderError("Password or Username is incorrect");
}

?>
<?php require('Fullui.php'); ?>
