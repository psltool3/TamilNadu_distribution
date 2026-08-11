<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../util/Logger.php');

if (!SessionCheck()) {
    echo "Session expired";
    return;
}

if (empty($_POST) || empty($_POST['fromid']) || empty($_POST['toid']) || empty($_POST['commodity'])) {
    die("Invalid request parameters.");
}

$fromid = mysqli_real_escape_string($con, $_POST['fromid']);
$toid = mysqli_real_escape_string($con, $_POST['toid']);
$commodity = mysqli_real_escape_string($con, $_POST['commodity']);

$query = "SELECT * FROM optimised_table_leg1 ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con, $query);
$id = "";
while ($row = mysqli_fetch_array($result)) {
    $id = $row["id"];
}

if (empty($id)) {
    die("No active table found.");
}

$tablename = "optimiseddata_leg1_" . $id;

$updateQuery = "UPDATE " . $tablename . " SET approve_admin='', new_id_admin='', new_name_admin='', reason_admin='', new_distance_admin='', district_change_approve='' WHERE from_id='$fromid' AND (to_id='$toid' OR `to`='$toid') AND commodity='$commodity'";

if (mysqli_query($con, $updateQuery)) {
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : '';
    writeLog("Admin User -> Reset Row Data Leg1 | " . $user . " | " . $fromid . " - " . $toid . " - " . $commodity);
    echo "success";
} else {
    echo "Error updating record: " . mysqli_error($con);
}

mysqli_close($con);
?>
