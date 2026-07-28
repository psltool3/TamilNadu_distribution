<?php

require('../util/Connection.php');
require '../vendor/autoload.php';
require('../util/SessionCheck.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function is_valid_table($con, $t) {
    if (empty($t)) return false;
    $chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $t) . "'");
    return ($chk && mysqli_num_rows($chk) > 0);
}

function fetch_optimised_rows($con, $tablename, $district, $columns, &$tableData) {
    if (!is_valid_table($con, $tablename)) return;

    $district_escaped = mysqli_real_escape_string($con, $district);

    if (!empty($district) && $district !== 'all') {
        $query = "SELECT * FROM $tablename WHERE REPLACE(LOWER(to_district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '')";
    } else {
        $query = "SELECT * FROM $tablename WHERE 1";
    }

    $result = mysqli_query($con, $query);
    if (!$result) return;
    $numrows = mysqli_num_rows($result);
    if ($numrows <= 0) return;

    while ($row = mysqli_fetch_array($result)) {
        // Apply admin override if present
        if (!empty($row['new_id_admin'])) {
            $wh_id = $row['new_id_admin'];
            $q_wh = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'";
            $r_wh = mysqli_query($con, $q_wh);
            if ($r_wh && mysqli_num_rows($r_wh) > 0) {
                $wh = mysqli_fetch_assoc($r_wh);
                $row["from_lat"]      = $wh['latitude'];
                $row["from_long"]     = $wh['longitude'];
                $row["from_district"] = $wh['district'];
            }
            $row["from_id"]   = $row['new_id_admin'];
            $row["from_name"] = $row['new_name_admin'];
            $row["distance"]  = $row['new_distance_admin'];
        } elseif (!empty($row['new_id_district']) && (isset($row['approve_admin']) && $row['approve_admin'] === 'yes')) {
            $wh_id = $row['new_id_district'];
            $q_wh = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'";
            $r_wh = mysqli_query($con, $q_wh);
            if ($r_wh && mysqli_num_rows($r_wh) > 0) {
                $wh = mysqli_fetch_assoc($r_wh);
                $row["from_lat"]      = $wh['latitude'];
                $row["from_long"]     = $wh['longitude'];
                $row["from_district"] = $wh['district'];
            }
            $row["from_id"]   = $row['new_id_district'];
            $row["from_name"] = $row['new_name_district'];
            $row["distance"]  = $row['new_distance_district'];
        }

        $temp = array();
        for ($i = 0; $i < count($columns); $i++) {
            $temp[] = isset($row[$columns[$i]]) ? $row[$columns[$i]] : '';
        }
        array_push($tableData, $temp);
    }
}

// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];

    $columns = ["scenario","from","from_state","from_id","from_name","from_district",
                "from_lat","from_long","to","to_state","to_id","to_name",
                "to_district","to_lat","to_long","commodity","quantity","distance"];

    $tablename  = isset($_GET['tableName'])  ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['tableName'])  : '';
    $tablename1 = isset($_GET['tableName1']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['tableName1']) : $tablename;
    $district   = isset($_GET['district'])   ? trim($_GET['district']) : '';

    $tableData = array();
    array_push($tableData, $columns);

    // Query primary table (tablename = leg2 or default)
    fetch_optimised_rows($con, $tablename, $district, $columns, $tableData);

    // Query secondary table (tablename1 = leg1) if different
    if (!empty($tablename1) && $tablename1 !== $tablename) {
        fetch_optimised_rows($con, $tablename1, $district, $columns, $tableData);
    }

    $filename = 'Optimised_Data';

    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $rowIndex = 1;
            foreach ($tableData as $rowData) {
                $columnIndex = 1;
                foreach ($rowData as $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
                    $columnIndex++;
                }
                $rowIndex++;
            }

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        case 'pdf':
            require('fpdf/fpdf.php');

            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->SetTextColor(0);
            foreach ($tableData as $row) {
                foreach ($row as $col) {
                    $pdf->Cell(30, 10, $col, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFillColor(255, 255, 255);
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
            echo $pdf->Output('S');
            break;

        default:
            echo 'Error: Invalid format specified.';
            break;
    }
} else {
    echo 'Error: Please specify a format in the GET request (e.g., ?format=pdf).';
}

function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

exit();