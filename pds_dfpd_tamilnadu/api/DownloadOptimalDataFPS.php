<?php

require('../util/Connection.php');
require '../vendor/autoload.php';
require('../util/SessionCheck.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    
    $columns = ["district","name","id","type","latitude","longitude","demand","demand_rice","demand_frice"];
	$columnsName = ["district","name","id","type","latitude","longitude","Allocation Wheat","Allocation Rice","Allocation_FRice"];
    $tablename = $_GET['tableName'];
    $district = isset($_GET['district']) ? $_GET['district'] : '';

    $whereClause = " WHERE 1";
    if (!empty($district) && $district != "all") {
        $district_escaped = mysqli_real_escape_string($con, $district);
        $whereClause = " WHERE district='$district_escaped'";
    }

	$tableData = array();
    array_push($tableData,$columnsName);

	$query = "SELECT * FROM ".$tablename . $whereClause;
    $result = mysqli_query($con,$query);
    $numrows = $result ? mysqli_num_rows($result) : 0;
    
    if($numrows>0){
        while($row = mysqli_fetch_assoc($result)){
            $lat = isset($row["latitude"]) ? $row["latitude"] : '';
            $lng = isset($row["longitude"]) ? $row["longitude"] : '';
            $dWheat = isset($row["demand"]) && $row["demand"] !== '' ? $row["demand"] : '0';
            $dRice = isset($row["demand_rice"]) && $row["demand_rice"] !== '' ? $row["demand_rice"] : '0';
            $dFRice = isset($row["demand_frice"]) && $row["demand_frice"] !== '' ? $row["demand_frice"] : '0';

            if (isset($row["Allocation_Wheat"]) && is_numeric($row["Allocation_Wheat"]) && floatval($row["Allocation_Wheat"]) < 40) {
                $lat = $row["Allocation_Wheat"];
                $lng = isset($row["Allocation_Rice"]) ? $row["Allocation_Rice"] : '';
                $dWheat = isset($row["Allocation_FRice"]) ? $row["Allocation_FRice"] : '0';
                $dRice = isset($row["latitude"]) ? $row["latitude"] : '0';
                $dFRice = isset($row["demand_frice"]) ? $row["demand_frice"] : '0';
            }

            $mapped = array(
                "district" => isset($row["district"]) ? $row["district"] : '',
                "name" => isset($row["name"]) ? $row["name"] : '',
                "id" => isset($row["id"]) ? $row["id"] : '',
                "type" => isset($row["type"]) ? $row["type"] : '',
                "latitude" => $lat,
                "longitude" => $lng,
                "demand" => $dWheat,
                "demand_rice" => $dRice,
                "demand_frice" => $dFRice
            );

            $temp = array();
            for($i=0;$i<count($columns);$i++){
                $colKey = $columns[$i];
                array_push($temp, isset($mapped[$colKey]) ? $mapped[$colKey] : '');
            }
            array_push($tableData,$temp);
        }
    }
	
    // Filename for the downloaded file
    $filename = 'table_data';

    // Set headers for the chosen format
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            // Create a new PhpSpreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Populate the spreadsheet with data
            $sheet->fromArray($tableData, null, 'A1');

            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');

            // Output the spreadsheet to the browser
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        default:
            echo 'Invalid format specified.';
            break;
    }
}

// Function to output data as CSV
function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}
?>