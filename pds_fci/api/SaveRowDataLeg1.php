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
$user = isset($_SESSION['user']) ? $_SESSION['user'] : '';

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

if (isset($_POST['id_approve']) && $_POST['id_approve'] !== "") {
    $id_approve = mysqli_real_escape_string($con, $_POST['id_approve']);
    if ($id_approve == "yes" || $id_approve == "no") {
        $updateApprove = "UPDATE " . $tablename . " SET district_change_approve='$id_approve' WHERE from_id='$fromid' AND (to_id='$toid' OR `to`='$toid') AND commodity='$commodity'";
        mysqli_query($con, $updateApprove);
        writeLog("User -> Save Row Data Leg1 | approve district change $id_approve -> " . $user . " | " . $fromid . " - " . $toid . " - " . $commodity);
    }
}

if (isset($_POST['approve_bool']) && $_POST['approve_bool'] !== "") {
    $approve_bool = mysqli_real_escape_string($con, $_POST['approve_bool']);
    if ($approve_bool == "yes") {
        $updateQuery = "UPDATE " . $tablename . " SET approve_admin='yes' WHERE from_id='$fromid' AND (to_id='$toid' OR `to`='$toid') AND commodity='$commodity'";
        mysqli_query($con, $updateQuery);
        writeLog("User -> Save Row Data Leg1 | approve admin change yes -> " . $user . " | " . $fromid . " - " . $toid . " - " . $commodity);
    } else if ($approve_bool == "same") {
        $updateQuery = "UPDATE " . $tablename . " SET approve_admin='no' WHERE from_id='$fromid' AND (to_id='$toid' OR `to`='$toid') AND commodity='$commodity'";
        mysqli_query($con, $updateQuery);
        writeLog("User -> Save Row Data Leg1 | approve admin change no -> " . $user . " | " . $fromid . " - " . $toid . " - " . $commodity);
    } else if ($approve_bool == "no") {
        $new_id_admin = mysqli_real_escape_string($con, isset($_POST['new_id_admin']) ? $_POST['new_id_admin'] : '');
        $reason_admin = mysqli_real_escape_string($con, isset($_POST['reason_admin']) ? $_POST['reason_admin'] : '');
        $raw_dist = isset($_POST['new_distance_admin']) ? $_POST['new_distance_admin'] : '';
        $new_distance_admin = mysqli_real_escape_string($con, $raw_dist !== '' ? abs(intval($raw_dist)) : '');

        $query_name = "SELECT name FROM warehouse WHERE id='$new_id_admin'";
        $result_name = mysqli_query($con, $query_name);
        $row_name = mysqli_fetch_assoc($result_name);
        $name = isset($row_name['name']) ? mysqli_real_escape_string($con, $row_name['name']) : '';

        $updateQuery = "UPDATE " . $tablename . " SET new_id_admin='$new_id_admin', new_name_admin='$name', approve_admin='yes', new_distance_admin='$new_distance_admin', reason_admin='$reason_admin' WHERE from_id='$fromid' AND (to_id='$toid' OR `to`='$toid') AND commodity='$commodity'";
        mysqli_query($con, $updateQuery);
        writeLog("User -> Save Row Data Leg1 | approve admin change id -> " . $user . " | " . $fromid . " - " . $toid . " | " . $new_id_admin);
    }
}

echo "success";
mysqli_close($con);
?>
