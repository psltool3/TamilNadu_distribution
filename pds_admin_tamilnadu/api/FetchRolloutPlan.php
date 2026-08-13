<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if(!SessionCheck()){
	return;
}

if(empty($_POST) || empty($_POST['month']) || empty($_POST['district'])){
	die("Something went wrong...");
}

$warehouse = array();
$fps = array();
$warehouse_optimised = array();

$allocation = 0;
$qkm = 0;
$qkm_optimised = 0;
$averagedistance = 0;

function addUnique($value, &$array) {
    if (!in_array($value, $array)) {
        $array[] = $value;
    }
	return;
}

$month = $_POST['month'];
$district = $_POST['district'];

// Validate month (letters, numbers, underscore)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $month)) {
    die("Invalid month format");
}

// Validate district (letters and spaces)
if (!preg_match('/^[a-zA-Z\s]+$/', $district)) {
    die("Invalid district name");
}

$parts = explode('_', $month);

if (count($parts) !== 2) {
    die("Invalid month_year format");
}

$month = $parts[0];
$year = $parts[1]; 

$query = "SELECT * FROM optimised_table WHERE month='$month' AND year='$year'";
$result = mysqli_query($con,$query);
$numrow = mysqli_num_rows($result);
$id = "";
if($numrow>0){
	$row = mysqli_fetch_assoc($result);
	$id = $row['id'];
}

$tablename = "optimiseddata_".$id;

$query = "SHOW TABLES LIKE '$tablename'";
$result = $con->query($query);

if ($result && $result->num_rows > 0) {
	// Normalize district name to match database spelling
	$dist_res = mysqli_query($con, "SELECT DISTINCT to_district FROM ".$tablename);
	if ($dist_res) {
		while($r = mysqli_fetch_assoc($dist_res)) {
			if (strcasecmp(str_replace(' ', '', $r['to_district']), str_replace(' ', '', $district)) === 0) {
				$district = $r['to_district'];
				break;
			}
		}
	}
	$district_escaped = mysqli_real_escape_string($con, $district);
	if (strtolower($district) === 'all' || empty($district)) {
		$query = "SELECT * FROM ".$tablename." WHERE status='implemented'";
	} else {
		$query = "SELECT * FROM ".$tablename." WHERE REPLACE(LOWER(to_district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '') AND status='implemented'";
	}
	$result = mysqli_query($con,$query);
	$numrows = $result ? mysqli_num_rows($result) : 0;
	$data = array();
	while($row = mysqli_fetch_assoc($result))
	{
		if(!empty($row['new_id_admin'])){
			$wh_id = $row['new_id_admin'];
			$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'";
			$result_warehouse = mysqli_query($con,$query_warehouse);
			if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
				$row_warehouse = mysqli_fetch_assoc($result_warehouse);
				$row["from_lat"] = $row_warehouse['latitude'];
				$row["from_long"] = $row_warehouse['longitude'];
				$row["from_district"] = $row_warehouse['district'];
			}
			$row["from_id"] = $row['new_id_admin'];
			$row["from_name"] = $row['new_name_admin'];
			$row["distance"] = $row['new_distance_admin'];
		}
		else if(!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin']=="yes"){
			$wh_id = $row['new_id_district'];
			$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'";
			$result_warehouse = mysqli_query($con,$query_warehouse);
			if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
				$row_warehouse = mysqli_fetch_assoc($result_warehouse);
				$row["from_lat"] = $row_warehouse['latitude'];
				$row["from_long"] = $row_warehouse['longitude'];
				$row["from_district"] = $row_warehouse['district'];
			}
			$row["from_id"] = $row['new_id_district'];
			$row["from_name"] = $row['new_name_district'];
			$row["distance"] = $row['new_distance_district'];
		}
		$data[] = $row;			
	}

	$query_summary = "SELECT * FROM ".$tablename;
	$result_summary = mysqli_query($con,$query_summary);
	if ($result_summary && mysqli_num_rows($result_summary) > 0) {
		while($row = mysqli_fetch_assoc($result_summary))
		{		
			addUnique($row["from_id"],$warehouse_optimised);
			$qkm_optimised = $qkm_optimised + ((float)$row["quantity"]) * (float)$row["distance"];
			if(!empty($row['new_id_admin'])){
				$wh_id = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_admin'];
				$row["from_name"] = $row['new_name_admin'];
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin']=="yes"){
				$wh_id = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_district'];
				$row["from_name"] = $row['new_name_district'];
				$row["distance"] = $row['new_distance_district'];
			}		
			addUnique($row["from_id"],$warehouse);
			addUnique($row["to_id"],$fps);
			$allocation = $allocation + (float)$row["quantity"];
			$qkm = $qkm + ((float)$row["quantity"]) * (float)$row["distance"];
		}
	}

	$averagedistance = ($allocation > 0) ? ($qkm / $allocation) : 0;
	$averagedistanceoptimised = ($allocation > 0) ? ($qkm_optimised / $allocation) : 0;
	$tableData = array();
	$tableData["WH_Used"] = count($warehouse);
	$tableData["FPS_Used"] = count($fps);
	$tableData["Demand"] = $allocation;
	$tableData["Total_QKM"] = $qkm;
	$tableData["Average_Distance"] = $averagedistance;
	$tableData["Scenario"] = "State Suggested";
	
	$tableData["WH_Used_Optimised"] = count($warehouse_optimised);
	$tableData["Total_QKM_Optimised"] = $qkm_optimised;
	$tableData["Average_Distance_Optimised"] = $averagedistanceoptimised;
	$tableData["Scenario_optimised"] = "Optimised";
	
	$tableData["WH_Used_Baseline"] = '245';
	$tableData["FPS_Used_Baseline"] = '34,691';
	$tableData["Demand_Baseline"] = '38,12,898';
	$tableData["Total_QKM_Baseline"] = '5,59,21,603';
	$tableData["Average_Distance_Baseline"] = '14.67';
	$tableData["Scenario_Baseline"] = "Baseline";
	
	$resultarray["data"] = $data;
	$resultarray["table"] = $tableData;
	echo json_encode($resultarray);
} else {
	$resultarray = [];
	$resultarray["data"] = array();
	$resultarray["table"] = array();
	echo json_encode($resultarray);
}
?>