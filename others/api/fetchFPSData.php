<?php
require('../util/Connection.php');

if(empty($_POST) || empty($_POST['district'])){
	die("Something went wrong...");
}

// Get the district from POST request and sanitize input (basic sanitization)
$district = $_POST['district'];

// Prepare the SQL query with a placeholder for the district value
$query = "SELECT * FROM fps WHERE district = ?";

// Initialize the prepared statement
$stmt = mysqli_prepare($con, $query);

// Check if the prepared statement was successfully created
if ($stmt === false) {
    die("Error preparing statement: " . mysqli_error($con));
}

// Bind the district parameter to the placeholder (s = string type)
mysqli_stmt_bind_param($stmt, 's', $district);

// Execute the prepared statement
mysqli_stmt_execute($stmt);

// Get the result of the query
$result = mysqli_stmt_get_result($stmt);

// Initialize the data array
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Initialize the result array
$resultarray = [];

// Check if data is null and make sure it's an empty array if no data is found
if ($data === null) {
    $data = [];
}

$resultarray["data"] = $data;

// Encode the result as JSON and output
echo json_encode($resultarray);

// Close the prepared statement and connection
mysqli_stmt_close($stmt);
mysqli_close($con);
?>
