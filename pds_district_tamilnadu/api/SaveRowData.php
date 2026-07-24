<?php
require('../util/Connection.php');
require('../util/SessionFunction.php');
require('../util/Logger.php');

if (!SessionCheck()) {
    echo "Session expired";
    return;
}

if (empty($_POST) || empty($_POST['fromid']) || empty($_POST['toid']) || empty($_POST['commodity']) || !isset($_POST['approve_bool'])) {
    die("Invalid request parameters.");
}

$fromid = mysqli_real_escape_string($con, $_POST['fromid']);
$toid = mysqli_real_escape_string($con, $_POST['toid']);
$commodity = mysqli_real_escape_string($con, $_POST['commodity']);
$approve_bool = mysqli_real_escape_string($con, $_POST['approve_bool']);
$district = isset($_SESSION['district_district']) ? $_SESSION['district_district'] : '';
$user = isset($_SESSION['district_user']) ? $_SESSION['district_user'] : '';

$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con, $query);
$id = "";
while ($row = mysqli_fetch_array($result)) {
    $id = $row["id"];
}

if (empty($id)) {
    die("No active table found.");
}

$tablename = "optimiseddata_" . $id;

if ($approve_bool == "yes") {
    $updateQuery = "UPDATE " . $tablename . " SET approve_district='yes' WHERE from_id='$fromid' AND (to_id='$toid' OR `to`='$toid') AND commodity='$commodity' AND to_district='$district'";
    if (mysqli_query($con, $updateQuery)) {
        writeLog("district User -> Save Row Data | approve district change yes -> " . $user . " | " . $fromid . " - " . $toid . " - " . $commodity);
        echo "success";
    } else {
        echo "Error updating record: " . mysqli_error($con);
    }
} else if ($approve_bool == "no") {
    $new_id_district = mysqli_real_escape_string($con, isset($_POST['new_id_district']) ? $_POST['new_id_district'] : '');
    $reason_district = mysqli_real_escape_string($con, isset($_POST['reason_district']) ? $_POST['reason_district'] : '');
    $new_distance_district = mysqli_real_escape_string($con, isset($_POST['new_distance_district']) ? $_POST['new_distance_district'] : '');

    $query_name = "SELECT name FROM warehouse WHERE id='$new_id_district'";
    $result_name = mysqli_query($con, $query_name);
    $row_name = mysqli_fetch_assoc($result_name);
    $name = isset($row_name['name']) ? mysqli_real_escape_string($con, $row_name['name']) : '';

    $updateQuery = "UPDATE " . $tablename . " SET new_id_district='$new_id_district', new_name_district='$name', approve_district='yes', new_distance_district='$new_distance_district', reason_district='$reason_district' WHERE from_id='$fromid' AND (to_id='$toid' OR `to`='$toid') AND commodity='$commodity' AND to_district='$district'";
    if (mysqli_query($con, $updateQuery)) {
        writeLog("district User -> Save Row Data | district user change id -> " . $user . " | " . $fromid . " - " . $toid . " - " . $commodity . " | " . $new_id_district);
        echo "success";
    } else {
        echo "Error updating record: " . mysqli_error($con);
    }
} else {
    echo "Invalid option";
}

mysqli_close($con);
?>
