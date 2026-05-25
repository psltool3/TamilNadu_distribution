<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if(!SessionCheck()){
	return;
}

//if(empty($_POST) || empty($_POST['month']) || empty($_POST['district'])){
//	die("Something went wrong...");
//}

if(empty($_POST)){
	die("Something went wrong...");
}

if (isset($_POST['fromid'])) {
    if ($_POST['fromid'] !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', $_POST['fromid'])) {
        die("Invalid format.");
    }
    $from_id = $_POST['fromid'];
}

if (isset($_POST['toid'])) {
    if ($_POST['toid'] !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', $_POST['toid'])) {
        die("Invalid format.");
    }
    $to_id = $_POST['toid'];
}

if (isset($_POST['approved'])) {
    if ($_POST['approved'] !== '' && !preg_match('/^[a-zA-Z\s]+$/', $_POST['approved'])) {
        die("Invalid format.");
    }
    $approved = $_POST['approved'];
}

if (isset($_POST['reviewed'])) {
    if ($_POST['reviewed'] !== '' && !preg_match('/^[a-zA-Z\s]+$/', $_POST['reviewed'])) {
        die("Invalid format.");
    }
    $reviewed = $_POST['reviewed'];
}


$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
$response = array();
$id = "";
while($row = mysqli_fetch_array($result))
{
	$id= $row["id"];
}


$tablename = "optimiseddata_".$id;

$district = $_SESSION['district_district'];
$reviewed = "";
$approved = "";
$from_id = "";
$to_id = "";

if(isset($_POST['fromid'])){
	$from_id = $_POST['fromid'];
}

if(isset($_POST['toid'])){
	$to_id = $_POST['toid'];
}

if(isset($_POST['approved'])){
	$approved = $_POST['approved'];
}

if(isset($_POST['reviewed'])){
	$reviewed = $_POST['reviewed'];
}



$query = "SELECT * FROM " . $tablename . " WHERE to_district='$district'";
if($reviewed=="reviewed"){
	$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district'";
}
else if($reviewed=="notreviewed"){
	$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district'";
}

if($approved=="approved"){
	$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district'";
}
else if($approved=="notapproved"){
	$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district'";
}
if($from_id!=""){
	$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND from_id='$from_id'";
	if($reviewed=="reviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district' AND from_id='$from_id'";
	}
	else if($reviewed=="notreviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district' AND from_id='$from_id'";
	}

	if($approved=="approved"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district' AND from_id='$from_id'";
	}
	else if($approved=="notapproved"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district' AND from_id='$from_id'";
	}
}
if($to_id!=""){
	$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND `to`='$to_id'";
	if($reviewed=="reviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district' AND `to`='$to_id'";
	}
	else if($reviewed=="notreviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district' AND `to`='$to_id'";
	}

	if($approved=="approved"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district' AND `to`='$to_id'";
	}
	else if($approved=="notapproved"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district' AND `to`='$to_id'";
	}
}
if($to_id!="" and $from_id!=""){
	$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND `to`='$to_id' AND from_id='$from_id'";
	if($reviewed=="reviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district' AND `to`='$to_id' AND from_id='$from_id'";
	}
	else if($reviewed=="notreviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district' AND `to`='$to_id' AND from_id='$from_id'";
	}

	if($approved=="approved"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district' AND `to`='$to_id' AND from_id='$from_id'";
	}
	else if($approved=="notapproved"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district' AND `to`='$to_id' AND from_id='$from_id' ";
	}
}

$result = mysqli_query($con,$query);
while($row = mysqli_fetch_array($result))
{
	$data[] = $row;
}

$query_warehouse = "SELECT * from warehouse WHERE district='$district' ";
$result_warehouse = mysqli_query($con,$query_warehouse);
while($row_warehouse = mysqli_fetch_array($result_warehouse)){
	$warehouse[] = $row_warehouse;
}
$resultarray = [];
if($data==null){
	$data = array();
}
$resultarray["data"] = $data;
$resultarray["warehouse"] = $warehouse;
echo json_encode($resultarray);

?>