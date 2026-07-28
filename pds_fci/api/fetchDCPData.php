<?php
require('../util/Connection.php');

$district = isset($_POST['district']) ? trim($_POST['district']) : '';

if(!empty($district) && strtolower($district) !== 'all'){
    $district_escaped = mysqli_real_escape_string($con, $district);
    $query = "SELECT * FROM dcp WHERE (LOWER(district)=LOWER('$district_escaped') OR LOWER(REPLACE(district, ' ', ''))=LOWER(REPLACE('$district_escaped', ' ', '')))";
} else {
    $query = "SELECT * FROM dcp WHERE 1";
}

$result = mysqli_query($con, $query);
$data = array();

if($result && mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $data[] = $row;
    }
}

$resultarray = [];
$resultarray["data"] = $data;
echo json_encode($resultarray);
?>
