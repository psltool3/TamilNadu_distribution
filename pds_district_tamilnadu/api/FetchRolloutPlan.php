<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if(!SessionCheck()){
	return;
}

if(empty($_SESSION) || !isset($_SESSION['district_district'])){
	die("Something went wrong..");
}

$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$id = "";
$rolled_out = "0";

if($result && mysqli_num_rows($result) > 0){
	$row = mysqli_fetch_assoc($result);
	$id = $row["id"];
	$rolled_out = isset($row["rolled_out"]) ? (string)$row["rolled_out"] : "0";
}

if($rolled_out !== "1" || empty($id)){
	$resultarray = array();
	$resultarray["data"] = array();
	$resultarray["implemented"] = 0;
	$resultarray["notimplemented"] = 0;
	echo json_encode($resultarray);
	exit;
}

$tablename = "optimiseddata_".$id;
$district = $_SESSION['district_district'];
$status = isset($_POST['status']) ? $_POST['status'] : '';
$data = array();

$query = "SHOW TABLES LIKE '$tablename'";
$result = $con->query($query);

if ($result && $result->num_rows > 0) {
	$district_escaped = mysqli_real_escape_string($con, $district);
	$district_cond = "REPLACE(LOWER(to_district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '')";
	$approved_cond = "approve_district='yes' AND approve_admin='yes'";
	
	$query_implemented = "SELECT * FROM ".$tablename." WHERE " . $district_cond . " AND " . $approved_cond . " AND status='implemented'";
	$result_implemented = mysqli_query($con,$query_implemented);
	$count_implemented = $result_implemented ? mysqli_num_rows($result_implemented) : 0;
	
	$query_notimplemented = "SELECT * FROM ".$tablename." WHERE " . $district_cond . " AND " . $approved_cond . " AND (status IS NULL OR status='' OR status<>'implemented')";
	$result_notimplemented = mysqli_query($con,$query_notimplemented);
	$count_notimplemented = $result_notimplemented ? mysqli_num_rows($result_notimplemented) : 0;
	
	$base_query = "SELECT * FROM ".$tablename." WHERE " . $district_cond . " AND " . $approved_cond;
	if($status=="implemented"){
		$query = $base_query . " AND status='implemented'";
	}
	else if($status=="not implemented"){
		$query = $base_query . " AND (status IS NULL OR status='' OR status<>'implemented')";
	}
	else {
		$query = $base_query;
	}
	
	$result = mysqli_query($con,$query);

	if($result){
		while($row = mysqli_fetch_assoc($result))
		{
			if($row['new_id_admin']!=null && $row['new_id_admin']!=""){
				$wid = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
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
			else if(($row['new_id_district']!=null && $row['new_id_district']!="") && $row['approve_admin']=="yes"){
				$wid = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
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
	}
	$resultarray["data"] = $data;
	$resultarray["implemented"] = $count_implemented;
	$resultarray["notimplemented"] = $count_notimplemented;
	echo json_encode($resultarray);
} else {
	$resultarray = array();
	$resultarray["data"] = array();
	$resultarray["implemented"] = 0;
	$resultarray["notimplemented"] = 0;
	echo json_encode($resultarray);
}
?>