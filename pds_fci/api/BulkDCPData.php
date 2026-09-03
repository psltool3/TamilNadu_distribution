FCI<?php
require('../util/Connection.php');
require('../structures/DCP.php');
require('../util/SessionFunction.php');
ini_set('max_execution_time', 3000);
require('../structures/Login.php');
$data_original = isset($_POST['data']) ? $_POST['data'] : null;
require('../util/Security.php');
if ($data_original !== null) {
    $_POST['data'] = $data_original;
}
require('../util/Logger.php');
require ('../util/Encryption.php');
$nonceValue = 'nonce_value';


if(!SessionCheck()){
	return;
}

require('Header.php');

$person = new Login;
$person->setUsername($_POST["username"]);
$Encryption = new Encryption();
$person->setPassword($Encryption->decrypt($_POST["password"], $nonceValue));

$mapData = [
    "District" => "district",
    "Name of FCI" => "name",
    "FCI ID" => "id",
    "Type" => "type",
    "Latitude" => "latitude",
    "Longitude" => "longitude",
    "Total Wheat Offered(Qtl)" => "demand",
	"Total Rice Offered(Qtl)" => "demand_rice",
	"Total FRice Offered(Qtl)" => "demand_frice",
	"Active/Not-Active" => "active"
];

// Reverse mapping
$reverseMapData = array_flip($mapData);

$districts = [];
$query = "SELECT name FROM districts WHERE 1";
$result = mysqli_query($con,$query);
$numrows = mysqli_num_rows($result);
if($numrows>0){
	while($row=mysqli_fetch_assoc($result)){
		array_push($districts,strtoupper(trim($row["name"])));
	}
}


function formatName($name) {
	$name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $name);
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

$redirect = 1;
$query = "SELECT * FROM login WHERE username='".$person->getUsername()."'";
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);

$dbHashedPassword = $row['password'];
if(password_verify($person->getPassword(), $dbHashedPassword)){
try{
	$fileName = $_FILES["file"]["tmp_name"];
	if ($_FILES["file"]["size"] > 0) {
		$file = fopen($fileName, "r");
		$i = 0;
		$district = -1;
		$name = -1;
		$id = -1;
		$type = -1;
		$demand = -1;
		$demand_rice = -1;
		$demand_frice = -1;
		$longitude = -1;
		$latitude = -1;
		$active = -1;
		while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
			if($i>0){
				if($district<0 or $name<0 or $id<0 or $type<0 or $demand<0 or $demand_rice<0 or $demand_frice<0 or $latitude<0 or $longitude<0 or $active<0){
					echo "Error : You have modified Template Header, please check";
					exit();
				}
				if(!isValidCoordinate($column[$latitude],'latitude') or !isValidCoordinate($column[$longitude],'longitude')){
					echo "Error : Check Latitude and Longitude Value Latitude: ".$column[$latitude]." Longitude: ".$column[$longitude];
					echo "</br>";
					$redirect = 0;
				}
				if(!isStringNumber($column[$demand]) || floatval($column[$demand]) < 0){
					echo "Error : Check Offered Wheat Value: ".$column[$demand];
					echo "</br>";
					$redirect = 0;
				}
				if(!isStringNumber($column[$demand_rice]) || floatval($column[$demand_rice]) < 0){
					echo "Error : Check Offered Rice Value: ".$column[$demand_rice];
					echo "</br>";
					$redirect = 0;
				}
				if(!isStringNumber($column[$demand_frice]) || floatval($column[$demand_frice]) < 0){
					echo "Error : Check Offered FRice Value: ".$column[$demand_frice];
					echo "</br>";
					$redirect = 0;
				}
				if(!in_array(strtoupper(trim($column[$district])), $districts)){
					echo "Error : Check District Name: ".$column[$district];
					echo "</br>";
					$redirect = 0;
				}
				
				if (!is_numeric($column[$latitude]) || $column[$latitude] <= 0 || $column[$latitude] >= 40) {
					echo "Error : Latitude must be greater than 0 and less than 40. Given: " . $column[$latitude];
					echo "</br>";
					$redirect = 0;
				}

				if (!is_numeric($column[$longitude]) || $column[$longitude] <= 65 || $column[$longitude] >= 100) {
					echo "Error : Longitude must be greater than 65 and less than 100. Given: " . $column[$longitude];
					echo "</br>";
					$redirect = 0;
				}	
				
				if (
					!isset($column[$id]) ||
					!preg_match('/^[A-Za-z0-9]+$/', $column[$id])
				) {
					echo "Error: FCI ID should not contain spaces or any special characters: " . ($column[$id] ?? 'Missing');
					echo "<br>";
					$redirect = 0;
				}
					
					
				if(!($column[$active]==0 || $column[$active]==1)){
					echo "Error : Check value of active/inactive column: ".$column[$active];
					echo "</br>";
					$redirect = 0;
				}
			}
			else{
				for($j=0;$j<count($column);$j++){
					switch($column[$j]){
						case $reverseMapData["district"]:
							$district = $j;
							break;
						case $reverseMapData["latitude"]:
							$latitude = $j;
							break;
						case $reverseMapData["longitude"]:
							$longitude = $j;
							break;
						case $reverseMapData["name"]:
							$name = $j;
							break;
						case $reverseMapData["id"]:
							$id = $j;
							break;
						case $reverseMapData["type"]:
							$type = $j;
							break;
						case $reverseMapData["demand"]:
							$demand = $j;
							break;
						case $reverseMapData["demand_rice"]:
							$demand_rice = $j;
							break;
						case $reverseMapData["demand_frice"]:
							$demand_frice = $j;
							break;
						case $reverseMapData["active"]:
							$active = $j;
							break;
					}
				}
			}
			$i = $i+1;
		}
	}
}
catch(Exception $e){
	echo "Error : Please check data in  .csv file";
}

if($redirect == 0){
	exit();
}

try{
	//if (isset($_POST["submit"])){
		$fileName = $_FILES["file"]["tmp_name"];
		if ($_FILES["file"]["size"] > 0) {
			
			$file = fopen($fileName, "r");
			$i = 0;
			$district = -1;
			$name = -1;
			$id = -1;
			$type = -1;
			$demand = -1;
			$demand_rice = -1;
			$demand_frice = -1;
			$longitude = -1;
			$latitude = -1;
			$active = -1;
			while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
				if($i>0){
					if($district<0 or $name<0 or $id<0 or $type<0 or $demand<0 or $demand_rice<0 or  $demand_frice<0 or $latitude<0 or $longitude<0 or $active<0){
						echo "Error : You have modified Template Header, please check";
						exit();
					}
					$DCP = new DCP;
					$uniqueid = uniqid("DCP_",);
					$DCP->setUniqueid(substr($uniqueid,0,15));
					$DCP->setDistrict(strtoupper(trim($column[$district])));
					$DCP->setLatitude($column[$latitude]);
					$DCP->setLongitude($column[$longitude]);
					$DCP->setName($column[$name]);
					$DCP->setId($column[$id]);
					$DCP->setType($column[$type]);
					$DCP->setDemand($column[$demand]);
					$DCP->setDemandRice($column[$demand_rice]);
					$DCP->setDemandfRice($column[$demand_frice]);
					$DCP->setActive($column[$active]);
					while(true){
						$query_check = $DCP->check($DCP);
						$query_result = mysqli_query($con, $query_check);
						$numrows = mysqli_num_rows($query_result);
						if($numrows==0){
							break;
						}
						else{
							$uniqueid = uniqid("DCP_",);
							$DCP->setUniqueid(substr($uniqueid,0,15));
						}
					}
					$query_insert_check = $DCP->checkInsert($DCP);
					$query_insert_result = mysqli_query($con, $query_insert_check);
					$numrows_insert = mysqli_num_rows($query_insert_result);
					if($numrows_insert==0){
						writeLog("User ->" ." DCP Added -> ". $_SESSION['user'] . "| " . $DCP->getName());
						$query_add = $DCP->insert($DCP);
						mysqli_query($con, $query_add);
					}
					else{
						echo "Error : DCP with id ".$DCP->getId()." Already Exist</br>";
						$redirect = 2;
					}
				}
					
				else{
					for($j=0;$j<count($column);$j++){
						switch($column[$j]){
							case $reverseMapData["district"]:
								$district = $j;
								break;
							case $reverseMapData["latitude"]:
								$latitude = $j;
								break;
							case $reverseMapData["longitude"]:
								$longitude = $j;
								break;
							case $reverseMapData["name"]:
								$name = $j;
								break;
							case $reverseMapData["id"]:
								$id = $j;
								break;
							case $reverseMapData["type"]:
								$type = $j;
								break;
							case $reverseMapData["demand"]:
								$demand = $j;
								break;
							case $reverseMapData["demand_rice"]:
								$demand_rice = $j;
								break;
							case $reverseMapData["demand_frice"]:
								$demand_frice = $j;
								break;
							case $reverseMapData["active"]:
								$active = $j;
								break;
						}
					}
				}
				$i = $i+1;
				
			}
			if($redirect==1){
				echo "<script>window.location.href = '../DCP.php';</script>";
			}
		}
	//}
	
}
catch(Exception $e){
	echo "Error : Please check data in  .csv file";
}
} 
else{
    echo "Error : Password or Username is incorrect";
}
?>
<?php require('Fullui.php');  ?>