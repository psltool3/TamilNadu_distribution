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

	$query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
	$result = mysqli_query($con,$query);
	$numrow = mysqli_num_rows($result);
	$id = "";
	if($numrow>0){
		$row = mysqli_fetch_assoc($result);
		$id = $row['id'];
	}

	$tablename = "optimiseddata_".$id;

	$reviewed = isset($_GET['reviewed']) ? mysqli_real_escape_string($con, $_GET['reviewed']) : '';
	$approved = isset($_GET['approved']) ? mysqli_real_escape_string($con, $_GET['approved']) : '';
	$from_id = isset($_GET['fromid']) ? mysqli_real_escape_string($con, $_GET['fromid']) : '';
	$to_id = isset($_GET['toid']) ? mysqli_real_escape_string($con, $_GET['toid']) : '';

	$query = "SELECT * FROM " . $tablename . " WHERE to_district='$district'";
	if($reviewed=="reviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district'";
	}
	else if($reviewed=="notreviewed"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district'";
	}

	if($approved=="approved"){
		$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district'";
	}
	else if($approved=="notapproved"){
		$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district'";
	}
	if($from_id!=""){
		$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND from_id='$from_id'";
		if($reviewed=="reviewed"){
			$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district' AND from_id='$from_id'";
		}
		else if($reviewed=="notreviewed"){
			$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district' AND from_id='$from_id'";
		}

		if($approved=="approved"){
			$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district' AND from_id='$from_id'";
		}
		else if($approved=="notapproved"){
			$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district' AND from_id='$from_id'";
		}
	}
	if($to_id!=""){
		$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND `to_id`='$to_id'";
		if($reviewed=="reviewed"){
			$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district' AND `to_id`='$to_id'";
		}
		else if($reviewed=="notreviewed"){
			$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district' AND `to_id`='$to_id'";
		}

		if($approved=="approved"){
			$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district' AND `to_id`='$to_id'";
		}
		else if($approved=="notapproved"){
			$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district' AND `to_id`='$to_id'";
		}
	}
	if($to_id!="" and $from_id!=""){
		$query = "SELECT * FROM ".$tablename." WHERE to_district='$district' AND `to_id`='$to_id' AND from_id='$from_id'";
		if($reviewed=="reviewed"){
			$query = "SELECT * FROM ".$tablename." WHERE approve_district='yes' AND to_district='$district' AND `to_id`='$to_id' AND from_id='$from_id'";
		}
		else if($reviewed=="notreviewed"){
			$query = "SELECT * FROM ".$tablename." WHERE (approve_district = '' OR approve_district IS NULL) AND to_district='$district' AND `to_id`='$to_id' AND from_id='$from_id'";
		}

		if($approved=="approved"){
			$query = "SELECT * FROM ".$tablename." WHERE approve_admin='yes' AND to_district='$district' AND `to_id`='$to_id' AND from_id='$from_id'";
		}
		else if($approved=="notapproved"){
			$query = "SELECT * FROM ".$tablename." WHERE (approve_admin='no' or approve_admin IS NULL) AND to_district='$district' AND `to_id`='$to_id' AND from_id='$from_id' ";
		}
	}

    $result = mysqli_query($con,$query);
    $numrows = mysqli_num_rows($result);
    $tableData = array();
    $tableData_pdf = array();
    array_push($tableData,$columns);
    array_push($tableData_pdf,$columns_pdf);

    if($numrows>0){
        while($row = mysqli_fetch_array($result)){
			if($row['new_id_admin']!=null or $row['new_id_admin']!=""){
				$id = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				$numrows_warehouse = mysqli_num_rows($result_warehouse);
				if($numrows_warehouse!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_admin'];
				$row["from_name"] = $row['new_name_admin'];
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(($row['new_id_district']!=null or $row['new_id_district']!="") and $row['admin_approve']=="yes"){
				$id = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				$numrows_warehouse = mysqli_num_rows($result_warehouse);
				if($numrows_warehouse!=0){
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
            for($i=0;$i<count($columns);$i++){
                array_push($temp,$row[$columns[$i]]);
            }
            for($i=0;$i<count($columns_pdf);$i++){
                array_push($temp_pdf,$row[$columns_pdf[$i]]);
            }
            array_push($tableData,$temp);
            array_push($tableData_pdf,$temp_pdf);
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