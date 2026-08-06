<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../structures/Login.php');

if(!SessionCheck()){
	return;
}

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
$id = "";
$rolled_out = "0";
if($result && mysqli_num_rows($result) > 0){
	$row = mysqli_fetch_array($result);
	$id = $row["id"];
	$rolled_out = isset($row["rolled_out"]) ? (string)$row["rolled_out"] : "0";
}

if($rolled_out !== "1" || empty($id)){
	echo json_encode(["data" => [], "warehouse" => []]);
	exit();
}

$tablename = "optimiseddata_".$id;
$district = isset($_SESSION['district_district']) ? $_SESSION['district_district'] : '';
$reviewed = isset($_POST['reviewed']) ? $_POST['reviewed'] : '';
$approved = isset($_POST['approved']) ? $_POST['approved'] : '';
$from_id = isset($_POST['fromid']) ? $_POST['fromid'] : '';
$to_id = isset($_POST['toid']) ? $_POST['toid'] : '';

$district_escaped = mysqli_real_escape_string($con, $district);
$where = array();
$where[] = "REPLACE(LOWER(to_district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '')";

if ($reviewed == "reviewed") {
    $where[] = "approve_district='yes'";
} else if ($reviewed == "notreviewed") {
    $where[] = "(approve_district = '' OR approve_district IS NULL)";
}

if ($approved == "approved") {
    $where[] = "approve_admin='yes'";
} else if ($approved == "notapproved") {
    $where[] = "(approve_admin='no' OR approve_admin IS NULL)";
}

if (!empty($from_id)) {
    $from_id_escaped = mysqli_real_escape_string($con, $from_id);
    $where[] = "from_id='$from_id_escaped'";
}

if (!empty($to_id)) {
    $to_id_escaped = mysqli_real_escape_string($con, $to_id);
    $where[] = "`to_id`='$to_id_escaped'";
}

$query = "SELECT * FROM " . $tablename . " WHERE " . implode(" AND ", $where);
$data = array();
$result = mysqli_query($con, $query);
if ($result) {
    while($row = mysqli_fetch_array($result)) {
        $data[] = $row;
    }
}

$query_warehouse = "SELECT * FROM warehouse WHERE REPLACE(LOWER(district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '')";
$warehouse = array();
$result_warehouse = mysqli_query($con, $query_warehouse);
if ($result_warehouse) {
    while($row_warehouse = mysqli_fetch_array($result_warehouse)) {
        $warehouse[] = $row_warehouse;
    }
}

$resultarray = array(
    "data" => $data,
    "warehouse" => $warehouse
);

echo json_encode($resultarray);
?>