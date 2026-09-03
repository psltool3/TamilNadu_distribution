<?php

require('../util/Connection.php');
require '../vendor/autoload.php';
require('../util/SessionCheck.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_GET['format'])) {
    $format = $_GET['format'];
    $tablename = isset($_GET['tableName']) ? $_GET['tableName'] : 'dcp';
    $district = isset($_GET['district']) ? $_GET['district'] : '';

    $whereClause = " WHERE 1";
    if (!empty($district) && $district != "all") {
        $district_escaped = mysqli_real_escape_string($con, $district);
        $whereClause = " WHERE district='$district_escaped'";
    }

    $chk_col = mysqli_query($con, "SHOW COLUMNS FROM " . mysqli_real_escape_string($con, $tablename) . " LIKE 'storage'");
    $has_storage = ($chk_col && mysqli_num_rows($chk_col) > 0);

    if ($has_storage) {
        $columns = ["district", "name", "id", "type", "latitude", "longitude", "storage"];
        $columnsName = ["District", "Name of FCI", "FCI ID", "Type of FCI", "Latitude", "Longitude", "Storage Capacity(Qtl)"];
    } else {
        $columns = ["district", "name", "id", "type", "latitude", "longitude", "demand", "demand_rice", "demand_frice"];
        $columnsName = ["District", "Name of FCI", "FCI ID", "Type of FCI", "Latitude", "Longitude", "Offered of Wheat(Qtl)", "Offered of Rice(Qtl)", "Offered of FRice(Qtl)"];
    }

    $tableData = array();
    array_push($tableData, $columnsName);

    $query = "SELECT * FROM " . mysqli_real_escape_string($con, $tablename) . $whereClause;
    $result = mysqli_query($con, $query);
    $numrows = $result ? mysqli_num_rows($result) : 0;

    if ($numrows > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $temp = array();
            $type_val = isset($row["type"]) ? $row["type"] : (isset($row["warehousetype"]) ? $row["warehousetype"] : '');
            
            if ($has_storage) {
                $temp[] = isset($row["district"]) ? $row["district"] : '';
                $temp[] = isset($row["name"]) ? $row["name"] : '';
                $temp[] = isset($row["id"]) ? $row["id"] : '';
                $temp[] = $type_val;
                $temp[] = isset($row["latitude"]) ? $row["latitude"] : '';
                $temp[] = isset($row["longitude"]) ? $row["longitude"] : '';
                $temp[] = isset($row["storage"]) ? $row["storage"] : '0';
            } else {
                $dWheat = isset($row['Offered_Wheat']) ? $row['Offered_Wheat'] : '0';
                $dRice  = isset($row['Offered_Rice']) ? $row['Offered_Rice'] : '0';
                $dFRice = isset($row['Offered_FRice']) ? $row['Offered_FRice'] : '0';

                $temp[] = isset($row["district"]) ? $row["district"] : '';
                $temp[] = isset($row["name"]) ? $row["name"] : '';
                $temp[] = isset($row["id"]) ? $row["id"] : '';
                $temp[] = $type_val;
                $temp[] = isset($row["latitude"]) ? $row["latitude"] : '';
                $temp[] = isset($row["longitude"]) ? $row["longitude"] : '';
                $temp[] = $dWheat;
                $temp[] = $dRice;
                $temp[] = $dFRice;
            }
            array_push($tableData, $temp);
        }
    }

    $filename = 'FCI_Data';

    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($tableData, null, 'A1');

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        default:
            echo 'Invalid format specified.';
            break;
    }
}

function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}
?>
