<?php

require('../util/Connection.php');
require '../vendor/autoload.php';
require('../util/SessionCheck.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function is_valid_table_wh($con, $t) {
    if (empty($t)) return false;
    $chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $t) . "'");
    return ($chk && mysqli_num_rows($chk) > 0);
}

function fetch_warehouse_rows($con, $tablename, $columns, &$tableData) {
    if (!is_valid_table_wh($con, $tablename)) return;
    $query = "SELECT * FROM " . $tablename . " WHERE 1";
    $result = mysqli_query($con, $query);
    if (!$result) return;
    $numrows = mysqli_num_rows($result);
    if ($numrows > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $temp = array();
            for ($i = 0; $i < count($columns); $i++) {
                $temp[] = isset($row[$columns[$i]]) ? $row[$columns[$i]] : "";
            }
            array_push($tableData, $temp);
        }
    }
}

// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    
    $columns = ["district", "name", "id", "type", "warehousetype", "latitude", "longitude", "storage"];

    $tablename  = isset($_GET['tableName'])  ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['tableName'])  : '';
    $tablename1 = isset($_GET['tableName1']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['tableName1']) : '';

    $tableData = array();
    array_push($tableData, $columns);

    // Query tablename1 first (primary: leg2 warehouse or default warehouse)
    fetch_warehouse_rows($con, $tablename1, $columns, $tableData);

    // Query tablename second if different (leg1 warehouse)
    if (!empty($tablename) && $tablename !== $tablename1) {
        fetch_warehouse_rows($con, $tablename, $columns, $tableData);
    }

    // Filename for the downloaded file
    $filename = 'Warehouse_Data';

    // Set headers for the chosen format
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

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        case 'pdf':
            require('fpdf/fpdf.php');

            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 7);

            $pdf->SetFillColor(200, 220, 255);
            $pdf->SetTextColor(0);
            foreach ($tableData as $row) {
                foreach ($row as $col) {
                    $pdf->Cell(22, 5, $col, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFillColor(255, 255, 255);
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
            echo $pdf->Output('S');
            break;

        default:
            echo 'Error : Invalid format specified.';
            break;
    }
} else {
    echo 'Error : Please specify a format in the GET request (e.g., ?format=pdf).';
}

// Function to output CSV data
function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

exit();