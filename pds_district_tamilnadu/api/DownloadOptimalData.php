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
    $district = $_SESSION['district_district'];
	$columns = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance"];
	$columns_pdf = ["scenario","from","from_id","from_name","from_district","from_lat","from_long","to","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance"];

	$tableData = array();
	$tableData_pdf = array();
	array_push($tableData,$columns);
	array_push($tableData_pdf,$columns_pdf);

	$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
	$result = mysqli_query($con,$query);
	$id = "";
	$rolled_out = "0";
	if($result && mysqli_num_rows($result) > 0){
		$row = mysqli_fetch_assoc($result);
		$id = $row['id'];
		$rolled_out = isset($row["rolled_out"]) ? (string)$row["rolled_out"] : "0";
	}

	if ($rolled_out === "1" && !empty($id)) {
		$tablename = "optimiseddata_".$id;
		$checkTable = $con->query("SHOW TABLES LIKE '$tablename'");
		if ($checkTable && $checkTable->num_rows > 0) {
			$district_escaped = mysqli_real_escape_string($con, $district);
			$district_cond = "REPLACE(LOWER(to_district), ' ', '') = REPLACE(LOWER('$district_escaped'), ' ', '')";

			$reviewed = isset($_GET['reviewed']) ? mysqli_real_escape_string($con, $_GET['reviewed']) : '';
			$approved = isset($_GET['approved']) ? mysqli_real_escape_string($con, $_GET['approved']) : '';
			$from_id = isset($_GET['fromid']) ? mysqli_real_escape_string($con, $_GET['fromid']) : '';
			$to_id = isset($_GET['toid']) ? mysqli_real_escape_string($con, $_GET['toid']) : '';

			$page = isset($_GET['page']) ? $_GET['page'] : '';
			$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($con, $_GET['status']) : '';

			if ($page === 'rollout') {
				$approved_cond = "approve_district='yes' AND approve_admin='yes'";
				$query = "SELECT * FROM " . $tablename . " WHERE " . $district_cond . " AND " . $approved_cond;
				if ($status_filter === 'not implemented') {
					$query .= " AND (status IS NULL OR status='' OR status<>'implemented')";
				} else {
					$query .= " AND status='implemented'";
				}
			} else {
				$query = "SELECT * FROM " . $tablename . " WHERE " . $district_cond;
				if ($reviewed === "reviewed") {
					$query .= " AND approve_district='yes'";
				} else if ($reviewed === "notreviewed") {
					$query .= " AND (approve_district = '' OR approve_district IS NULL)";
				}

				if ($approved === "approved") {
					$query .= " AND approve_admin='yes'";
				} else if ($approved === "notapproved") {
					$query .= " AND (approve_admin='no' OR approve_admin IS NULL)";
				}

				if ($status_filter === 'implemented') {
					$query .= " AND status='implemented'";
				} else if ($status_filter === 'not implemented') {
					$query .= " AND (status IS NULL OR status='' OR status<>'implemented')";
				}

				if ($from_id !== '') {
					$query .= " AND from_id='$from_id'";
				}
				if ($to_id !== '') {
					$query .= " AND `to_id`='$to_id'";
				}
			}

			$result = mysqli_query($con, $query);
			if ($result && mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_assoc($result)) {
					if ($row['new_id_admin'] != null && $row['new_id_admin'] != "") {
						$wid = $row['new_id_admin'];
						$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
						$result_warehouse = mysqli_query($con, $query_warehouse);
						if ($result_warehouse && mysqli_num_rows($result_warehouse) != 0) {
							$row_warehouse = mysqli_fetch_assoc($result_warehouse);
							$row["from_lat"] = $row_warehouse['latitude'];
							$row["from_long"] = $row_warehouse['longitude'];
							$row["from_district"] = $row_warehouse['district'];
						}
						$row["from_id"] = $row['new_id_admin'];
						$row["from_name"] = $row['new_name_admin'];
						$row["distance"] = $row['new_distance_admin'];
					} else if (($row['new_id_district'] != null && $row['new_id_district'] != "") && $row['approve_admin'] == "yes") {
						$wid = $row['new_id_district'];
						$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$wid'";
						$result_warehouse = mysqli_query($con, $query_warehouse);
						if ($result_warehouse && mysqli_num_rows($result_warehouse) != 0) {
							$row_warehouse = mysqli_fetch_assoc($result_warehouse);
							$row["from_lat"] = $row_warehouse['latitude'];
							$row["from_long"] = $row_warehouse['longitude'];
							$row["from_district"] = $row_warehouse['district'];
						}
						$row["from_id"] = $row['new_id_district'];
						$row["from_name"] = $row['new_name_district'];
						$row["distance"] = $row['new_distance_district'];
					}
					$temp = array();
					$temp_pdf = array();
					for ($i = 0; $i < count($columns); $i++) {
						array_push($temp, $row[$columns[$i]]);
					}
					for ($i = 0; $i < count($columns_pdf); $i++) {
						array_push($temp_pdf, $row[$columns_pdf[$i]]);
					}
					array_push($tableData, $temp);
					array_push($tableData_pdf, $temp_pdf);
				}
			}
		}
	}
    
    // Filename for the downloaded file
    $filename = 'table_data';

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
            // Create a new PhpSpreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Insert data tableData
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
			$pdf->SetFont('Arial', 'B', 15); // Set initial font size

			// Calculate column width based on the number of columns and page width
			$pageWidth = $pdf->GetPageWidth() - 20; // Subtract margins (10 mm each side)
			$numCols = count($tableData_pdf[0]) + 2; // Assuming all rows have the same number of columns
			$colWidth = $pageWidth / $numCols;
			$originalColWidth = $colWidth;

			// Function to add a row to the PDF with dynamic font size adjustment
			function addRow($pdf, $row, $colWidth, $isHeader = false) {
				global $originalColWidth;
				global $colWidth;
				$pdf->SetFillColor($isHeader ? 200 : 255, $isHeader ? 220 : 255, $isHeader ? 255 : 255);
				$i = 0;
				foreach ($row as $col) {
					$colStr = ($col === null) ? '' : (string)$col;
					$i = $i + 1;
					if($i==10){
						$colWidth = $colWidth*3;
					}else{
						$colWidth = $originalColWidth;
					}
					$fontSize = 12;
					$pdf->SetFont('Arial', 'B', $fontSize);
					// Reduce font size if text is too wide for the cell
					while ($pdf->GetStringWidth($colStr) > $colWidth - 2 && $fontSize > 1) {
						$fontSize -= 1;
						$pdf->SetFont('Arial', 'B', $fontSize);
					}
					$pdf->Cell($colWidth, 10, $colStr, 1, 0, 'C', true);
				}
				$pdf->Ln();
			}

			// Add the header
			addRow($pdf, $tableData_pdf[0], $colWidth, true);

			// Add the data rows
			$rowHeight = 10;

			for ($i = 1; $i < count($tableData_pdf); $i++) {
				if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) { // Check if we need to add a new page
					$pdf->AddPage();
					addRow($pdf, $tableData_pdf[0], $colWidth, true); // Add the header again on the new page
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

// Function to output CSV data
function outputCSV($data) {
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

exit();

?>