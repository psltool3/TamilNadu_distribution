<?php
ob_start();

require('../util/Connection.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$month = "";
$year = "";
$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
$result = mysqli_query($con,$query);
if ($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_array($result)) {
        $month = $row["month"];
        $year = $row["year"];
    }
}

// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    $district = isset($_GET['district']) ? $_GET['district'] : '';
    
    $columns     = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance","RO Accepted","FCI Release Warehouse","Reason for not Approve","Distance","status"];
    $columns_pdf = ["scenario","from","from_id","from_name","from_district","from_lat","from_long","to","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance","RO Accepted","FCI Release Warehouse","Reason for not Approve","Distance","status"];

    $fields     = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance","approve_district","new_id_admin","reason_admin","new_distance_admin","status"];
    $fields_pdf = ["scenario","from","from_id","from_name","from_district","from_lat","from_long","to","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance","approve_district","new_id_admin","reason_admin","new_distance_admin","status"];

    $month_escaped = mysqli_real_escape_string($con, $month);
    $year_escaped = mysqli_real_escape_string($con, $year);

    $tableData = array();
    $tableData_pdf = array();
    array_push($tableData,$columns);
    array_push($tableData_pdf,$columns_pdf);

    $query = "SELECT * FROM optimised_table WHERE month='$month_escaped' AND year='$year_escaped'";
    $result = mysqli_query($con,$query);
    $run_id = "";
    if($result && mysqli_num_rows($result)>0){
        $row = mysqli_fetch_assoc($result);
        $run_id = $row['id'];
    }

    if(!empty($run_id)){
        $tablename = "optimiseddata_".$run_id;
        $chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tablename) . "'");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $query = "SELECT * FROM ".$tablename." WHERE 1";
            
            if($district!="" and $district!="all"){
                $district_escaped = mysqli_real_escape_string($con, $district);
                $query = "SELECT * FROM ".$tablename." WHERE to_district='$district_escaped'";
            }

            $result = mysqli_query($con,$query);
            if ($result && mysqli_num_rows($result)>0) {
                while($row = mysqli_fetch_array($result)){
                    $wh_id = "";
                    if (!empty($row['new_id_admin'])) {
                        $wh_id = $row['new_id_admin'];
                    } else if (!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin'] === 'yes') {
                        $wh_id = $row['new_id_district'];
                    }

                    if (!empty($wh_id)) {
                        $query_warehouse = "SELECT latitude,longitude,district FROM warehouse_".$run_id." WHERE id='" . mysqli_real_escape_string($con, $wh_id) . "'";
                        $result_warehouse = @mysqli_query($con,$query_warehouse);
                        if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
                            $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                            $row["from_lat"] = $row_warehouse['latitude'];
                            $row["from_long"] = $row_warehouse['longitude'];
                            $row["from_district"] = $row_warehouse['district'];
                        }
                        if (!empty($row['new_id_admin'])) {
                            $row["from_id"] = $row['new_id_admin'];
                            $row["from_name"] = $row['new_name_admin'];
                            $row["distance"] = $row['new_distance_admin'];
                        } else {
                            $row["from_id"] = $row['new_id_district'];
                            $row["from_name"] = $row['new_name_district'];
                            $row["distance"] = $row['new_distance_district'];
                        }
                    }

                    $approve_admin_val = isset($row['approve_admin']) ? $row['approve_admin'] : '';
                    if ($approve_admin_val === "yes" && empty($row['new_id_admin'])) {
                        $row['status'] = "Approved";
                    } else if ($approve_admin_val === "yes" || $approve_admin_val === "no") {
                        $row['status'] = "Not Approved";
                    } else {
                        $row['status'] = !empty($row['status']) ? ucfirst($row['status']) : "Pending";
                    }

                    $temp = array();
                    $temp_pdf = array();
                    for($i=0;$i<count($fields);$i++){
                        $temp[] = isset($row[$fields[$i]]) ? $row[$fields[$i]] : '';
                    }
                    for($i=0;$i<count($fields_pdf);$i++){
                        $temp_pdf[] = isset($row[$fields_pdf[$i]]) ? $row[$fields_pdf[$i]] : '';
                    }
                    array_push($tableData,$temp);
                    array_push($tableData_pdf,$temp_pdf);
                }
            }
        }
    }

    $query = "SELECT * FROM optimised_table_leg1 WHERE month='$month_escaped' AND year='$year_escaped'";
    $result = mysqli_query($con,$query);
    $run_id_leg1 = "";
    if($result && mysqli_num_rows($result)>0){
        $row = mysqli_fetch_assoc($result);
        $run_id_leg1 = $row['id'];
    }

    if(!empty($run_id_leg1)){
        $tablename = "optimiseddata_leg1_".$run_id_leg1;
        $chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tablename) . "'");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $query = "SELECT * FROM ".$tablename." WHERE 1";
            
            if($district!="" and $district!="all"){
                $district_escaped = mysqli_real_escape_string($con, $district);
                $query = "SELECT * FROM ".$tablename." WHERE to_district='$district_escaped'";
            }

            $result = mysqli_query($con,$query);
            if ($result && mysqli_num_rows($result)>0) {
                while($row = mysqli_fetch_array($result)){
                    $wh_id = "";
                    if (!empty($row['new_id_admin'])) {
                        $wh_id = $row['new_id_admin'];
                    } else if (!empty($row['new_id_district']) && isset($row['approve_admin']) && $row['approve_admin'] === 'yes') {
                        $wh_id = $row['new_id_district'];
                    }

                    if (!empty($wh_id)) {
                        $query_warehouse = "SELECT latitude,longitude,district FROM warehouse_leg1_".$run_id_leg1." WHERE id='" . mysqli_real_escape_string($con, $wh_id) . "'";
                        $result_warehouse = @mysqli_query($con,$query_warehouse);
                        if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
                            $row_warehouse = mysqli_fetch_assoc($result_warehouse);
                            $row["from_lat"] = $row_warehouse['latitude'];
                            $row["from_long"] = $row_warehouse['longitude'];
                            $row["from_district"] = $row_warehouse['district'];
                        }
                        if (!empty($row['new_id_admin'])) {
                            $row["from_id"] = $row['new_id_admin'];
                            $row["from_name"] = $row['new_name_admin'];
                            $row["distance"] = $row['new_distance_admin'];
                        } else {
                            $row["from_id"] = $row['new_id_district'];
                            $row["from_name"] = $row['new_name_district'];
                            $row["distance"] = $row['new_distance_district'];
                        }
                    }

                    $approve_admin_val = isset($row['approve_admin']) ? $row['approve_admin'] : '';
                    if ($approve_admin_val === "yes" && empty($row['new_id_admin'])) {
                        $row['status'] = "Approved";
                    } else if ($approve_admin_val === "yes" || $approve_admin_val === "no") {
                        $row['status'] = "Not Approved";
                    } else {
                        $row['status'] = !empty($row['status']) ? ucfirst($row['status']) : "Pending";
                    }

                    $temp = array();
                    $temp_pdf = array();
                    for($i=0;$i<count($fields);$i++){
                        $temp[] = isset($row[$fields[$i]]) ? $row[$fields[$i]] : '';
                    }
                    for($i=0;$i<count($fields_pdf);$i++){
                        $temp_pdf[] = isset($row[$fields_pdf[$i]]) ? $row[$fields_pdf[$i]] : '';
                    }
                    array_push($tableData,$temp);
                    array_push($tableData_pdf,$temp_pdf);
                }
            }
        }
    }
    
    // Filename for the downloaded file
    $filename = 'Optimised_Data_Leg1_All';

    // Set headers for the chosen format
    switch ($format) {
        case 'csv':
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
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
            $pdf->SetFont('Arial', 'B', 15);

            $pageWidth = $pdf->GetPageWidth() - 20;
            $numCols = count($tableData_pdf[0]);
            $colWidth = ($numCols > 0) ? ($pageWidth / $numCols) : 10;
            $originalColWidth = $colWidth;

            function addRow($pdf, $row, $colWidth, $isHeader = false) {
                global $originalColWidth;
                global $colWidth;
                $pdf->SetFillColor($isHeader ? 200 : 255, $isHeader ? 220 : 255, $isHeader ? 255 : 255);
                $i = 0;
                foreach ($row as $col) {
                    $i = $i + 1;
                    $cellWidth = ($i == 10) ? $colWidth * 2 : $originalColWidth;
                    $fontSize = 8;
                    $pdf->SetFont('Arial', 'B', $fontSize);
                    while ($pdf->GetStringWidth($col) > $cellWidth - 2 && $fontSize > 1) {
                        $fontSize -= 1;
                        $pdf->SetFont('Arial', 'B', $fontSize);
                    }
                    $pdf->Cell($cellWidth, 8, $col, 1, 0, 'C', true);
                }
                $pdf->Ln();
            }

            addRow($pdf, $tableData_pdf[0], $colWidth, true);

            $rowHeight = 8;

            for ($i = 1; $i < count($tableData_pdf); $i++) {
                if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) {
                    $pdf->AddPage();
                    addRow($pdf, $tableData_pdf[0], $colWidth, true);
                }
                addRow($pdf, $tableData_pdf[$i], $colWidth);
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
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

function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

exit();