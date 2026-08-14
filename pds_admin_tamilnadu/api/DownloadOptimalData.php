<?php
ob_start();

require('../util/Connection.php');
require '../vendor/autoload.php';
require('../util/SessionCheck.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];

    $columns     = ["scenario","from","from_state","from_id","from_name","from_district",
                    "from_lat","from_long","to","to_state","to_id","to_name",
                    "to_district","to_lat","to_long","commodity","quantity","distance","status"];
    $columns_pdf = ["scenario","from","from_id","from_name","from_district",
                    "from_lat","from_long","to","to_id","to_name",
                    "to_district","to_lat","to_long","commodity","quantity","distance"];

    $month_raw = isset($_GET['month'])   ? trim($_GET['month'])   : '';
    $district  = isset($_GET['district']) ? trim($_GET['district']) : '';

    // Parse month_year (e.g. "jan_2026")
    $month = "";
    $year  = "";
    if (strpos($month_raw, '_') !== false) {
        $parts = explode('_', $month_raw);
        $month = $parts[0];
        $year  = $parts[1];
    }

    // Lookup run ID
    $run_id = "";
    if (!empty($month) && !empty($year)) {
        $q = "SELECT id FROM optimised_table WHERE month='$month' AND year='$year' LIMIT 1";
        $r = mysqli_query($con, $q);
        if ($r && mysqli_num_rows($r) > 0) {
            $run_id = mysqli_fetch_assoc($r)['id'];
        }
    }
    if (empty($run_id)) {
        $q = "SELECT id FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
        $r = mysqli_query($con, $q);
        if ($r && mysqli_num_rows($r) > 0) {
            $run_id = mysqli_fetch_assoc($r)['id'];
        }
    }

    $tableData     = array();
    $tableData_pdf = array();
    array_push($tableData,     $columns);
    array_push($tableData_pdf, $columns_pdf);

    if (!empty($run_id)) {
        $tablename = "optimiseddata_" . $run_id;

        // Guard: check table exists before querying
        $chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tablename) . "'");
        if ($chk && mysqli_num_rows($chk) > 0) {

            $district_escaped = mysqli_real_escape_string($con, $district);
            if (!empty($district) && strtolower($district) !== "all") {
                $query = "SELECT * FROM $tablename WHERE REPLACE(LOWER(to_district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '')";
            } else {
                $query = "SELECT * FROM $tablename";
            }

            $result  = mysqli_query($con, $query);
            $numrows = $result ? mysqli_num_rows($result) : 0;

            if ($numrows > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    // Apply admin override
                    if (!empty($row['new_id_admin'])) {
                        $wh_id = $row['new_id_admin'];
                        $r_wh  = mysqli_query($con, "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'");
                        if ($r_wh && mysqli_num_rows($r_wh) > 0) {
                            $wh = mysqli_fetch_assoc($r_wh);
                            $row["from_lat"]      = $wh['latitude'];
                            $row["from_long"]     = $wh['longitude'];
                            $row["from_district"] = $wh['district'];
                        }
                        $row["from_id"]   = $row['new_id_admin'];
                        $row["from_name"] = $row['new_name_admin'];
                        $row["distance"]  = $row['new_distance_admin'];
                    } elseif (!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin'] === 'yes') {
                        $wh_id = $row['new_id_district'];
                        $r_wh  = mysqli_query($con, "SELECT latitude,longitude,district FROM warehouse WHERE id='$wh_id'");
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

                    $temp     = array();
                    $temp_pdf = array();
                    for ($i = 0; $i < count($columns); $i++) {
                        $temp[] = isset($row[$columns[$i]]) ? $row[$columns[$i]] : '';
                    }
                    for ($i = 0; $i < count($columns_pdf); $i++) {
                        $temp_pdf[] = isset($row[$columns_pdf[$i]]) ? $row[$columns_pdf[$i]] : '';
                    }
                    array_push($tableData,     $temp);
                    array_push($tableData_pdf, $temp_pdf);
                }
            }
        }
    }

    $filename = 'Rollout_Plan';

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
                    $sheet->setCellValue([$columnIndex, $rowIndex], $value ?? '');
                    $columnIndex++;
                }
                $rowIndex++;
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        case 'pdf':
            require('fpdf/fpdf.php');

            $pdf = new FPDF('L', 'mm', 'A4');
            $pdf->AddPage();

            // Guard: need at least the header row
            if (count($tableData_pdf) < 1) {
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'No data available for selected filters.', 0, 1, 'C');
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                echo $pdf->Output('S');
                break;
            }

            $pageWidth    = $pdf->GetPageWidth() - 20;
            $numCols      = count($tableData_pdf[0]);
            $colWidth     = ($numCols > 0) ? ($pageWidth / ($numCols + 2)) : 20;
            $origColWidth = $colWidth;

            function addPdfRow($pdf, $row, $colWidth, $origColWidth, $isHeader = false) {
                $pdf->SetFillColor($isHeader ? 200 : 255, $isHeader ? 220 : 255, $isHeader ? 255 : 255);
                $i = 0;
                foreach ($row as $col) {
                    $i++;
                    $cellWidth = ($i == 10) ? $colWidth * 3 : $origColWidth;
                    $fontSize  = 7;
                    $pdf->SetFont('Arial', $isHeader ? 'B' : '', $fontSize);
                    while ($pdf->GetStringWidth($col) > $cellWidth - 2 && $fontSize > 4) {
                        $fontSize--;
                        $pdf->SetFont('Arial', $isHeader ? 'B' : '', $fontSize);
                    }
                    $pdf->Cell($cellWidth, 8, $col, 1, 0, 'C', true);
                }
                $pdf->Ln();
            }

            // Header row
            addPdfRow($pdf, $tableData_pdf[0], $colWidth, $origColWidth, true);

            // Data rows
            $rowHeight = 8;
            for ($i = 1; $i < count($tableData_pdf); $i++) {
                if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) {
                    $pdf->AddPage();
                    addPdfRow($pdf, $tableData_pdf[0], $colWidth, $origColWidth, true);
                }
                addPdfRow($pdf, $tableData_pdf[$i], $colWidth, $origColWidth, false);
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