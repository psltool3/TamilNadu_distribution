<?php
require('../util/Connection.php');

if(empty($_POST) || empty($_POST['tablename'])){
	die("Something went wrong...");
}

$district = isset($_POST['district']) ? $_POST['district'] : '';
$tablename = $_POST['tablename'];
$tablename1 = isset($_POST['tablename1']) ? $_POST['tablename1'] : $tablename;

function is_valid_table($con, $t) {
    if (empty($t)) return false;
    $chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $t) . "'");
    return ($chk && mysqli_num_rows($chk) > 0);
}

$data = array();

$whereClause = " WHERE 1";
if(!empty($district) && strtolower($district) != "all"){
	$district_escaped = mysqli_real_escape_string($con, $district);
	$whereClause = " WHERE (LOWER(to_district)=LOWER('$district_escaped') OR LOWER(REPLACE(to_district, ' ', ''))=LOWER(REPLACE('$district_escaped', ' ', '')))";
}

if(is_valid_table($con, $tablename)){
	$query = "SELECT * FROM ".$tablename . $whereClause;
	$result = mysqli_query($con,$query);
	if($result){
		while($row = mysqli_fetch_assoc($result))
		{
			if(!empty($row['new_id_admin'])){
				$wid = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse) > 0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_admin'];
				$row["from_name"] = $row['new_name_admin'];
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(!empty($row['new_id_district']) && ((isset($row['approve_admin']) && $row['approve_admin']=="yes") || (isset($row['admin_approve']) && $row['admin_approve']=="yes"))){
				$wid = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse) > 0){
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
	}
}

$resultarray = array();
$resultarray["data"] = $data;

if(!empty($tablename1) && $tablename1 != $tablename && is_valid_table($con, $tablename1)){
	$data1 = array();
	$query = "SELECT * FROM ".$tablename1 . $whereClause;
	$result = mysqli_query($con,$query);
	if($result){
		while($row = mysqli_fetch_assoc($result))
		{
			if(!empty($row['new_id_admin'])){
				$wid = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse) > 0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_admin'];
				$row["from_name"] = $row['new_name_admin'];
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(!empty($row['new_id_district']) && ((isset($row['approve_admin']) && $row['approve_admin']=="yes") || (isset($row['admin_approve']) && $row['admin_approve']=="yes"))){
				$wid = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse) > 0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_district'];
				$row["from_name"] = $row['new_name_district'];
				$row["distance"] = $row['new_distance_district'];
			}
			$data1[] = $row;
		}
	}
	$resultarray["data1"] = $data1;
}

echo json_encode($resultarray);
?>
