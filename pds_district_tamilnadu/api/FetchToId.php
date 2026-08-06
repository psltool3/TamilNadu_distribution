<?php
require('../util/Connection.php');
require('../structures/District.php');
require('../util/SessionFunction.php');

if(!SessionCheck()){
	return;
}

$district = isset($_SESSION['district_district']) ? $_SESSION['district_district'] : '';
$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$id = "";
$rolled_out = "0";
if($result && mysqli_num_rows($result) > 0){
	$row = mysqli_fetch_assoc($result);
	$id = $row['id'];
	$rolled_out = isset($row["rolled_out"]) ? (string)$row["rolled_out"] : "0";
}

if($rolled_out !== "1" || empty($id)){
	echo json_encode([]);
	exit();
}

$tablename = "optimiseddata_".$id;
$district_escaped = mysqli_real_escape_string($con, $district);
$result = $con->query("SELECT DISTINCT `to_id` from $tablename WHERE REPLACE(LOWER(to_district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '')");

$rows = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}
echo json_encode($rows);
?>