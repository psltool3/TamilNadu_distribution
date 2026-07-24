<?php
require('../util/Connection.php');

if(empty($_POST) || empty($_POST['tablename'])){
	die("Something went wrong...");
}

$district = isset($_POST['district']) ? $_POST['district'] : '';
$tablename = $_POST['tablename'];

$whereClause = " WHERE 1";
if(!empty($district) && $district != "all"){
	$district_escaped = mysqli_real_escape_string($con, $district);
	$whereClause = " WHERE district='$district_escaped'";
}

$query = "SELECT * FROM ".$tablename . $whereClause;
$result = mysqli_query($con,$query);
$data = array();

if($result){
	while($row = mysqli_fetch_assoc($result)){
		$data[] = $row;
	}
}

$resultarray = array();
$resultarray["data"] = $data;
echo json_encode($resultarray);
?>
