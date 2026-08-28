<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require 'vendor/autoload.php';
require('api/fpdf/fpdf.php');

$columns_pdf = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance","status"];

$filename = 'table_data';

function get_safe_optimised_table($con, $prefix, $id_val) {
    if (empty($id_val)) return '';
    $id_val = preg_replace('/[^a-zA-Z0-9_-]/', '', $id_val);
    $id_lower = strtolower($id_val);
    
    $chk1 = mysqli_query($con, "SHOW TABLES LIKE '" . $prefix . mysqli_real_escape_string($con, $id_val) . "'");
    if ($chk1 && mysqli_num_rows($chk1) > 0) {
        return $prefix . $id_val;
    }
    $chk2 = mysqli_query($con, "SHOW TABLES LIKE '" . $prefix . mysqli_real_escape_string($con, $id_lower) . "'");
    if ($chk2 && mysqli_num_rows($chk2) > 0) {
        return $prefix . $id_lower;
    }
    return '';
}

$id = isset($_POST['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['id']) : '';
$tablename = get_safe_optimised_table($con, "optimiseddata_", $id);
$tablename1 = $tablename;
$leg = 0;
$leg_id = '';

if(isset($_POST['step'])){
	if($_POST['step']=="leg1"){
		$leg = 1;
		$tablename = get_safe_optimised_table($con, "optimiseddata_leg1_", $id);
		$tablename1 = $tablename;
	}
	if($_POST['step']=="all"){
		$leg = 2;
		$leg_id = isset($_POST['legid']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['legid']) : '';
		$tablename = get_safe_optimised_table($con, "optimiseddata_", $id);
		$tablename1 = get_safe_optimised_table($con, "optimiseddata_leg1_", $leg_id);
	}
}

$month = "";
$date = "";
$cost = "";
$cost1 = "";

if(!empty($id)){
		$query = "SELECT * FROM optimised_table WHERE id='$id'";
	$result = mysqli_query($con,$query);
	if($result && mysqli_num_rows($result)>0){
		while($row=mysqli_fetch_assoc($result)){
			$month = $row["month"];
			$date = $row["last_updated"];
			$cost = $row["cost"];
		}
	}
}

$allocation = 0;
$qkm = 0;
$qkm_optimised = 0;
$averagedistanceoptimised = 0;

if(!empty($tablename)){
	$query = "SELECT * FROM $tablename WHERE 1";
	$result = mysqli_query($con,$query);
	if($result){
		while($row = mysqli_fetch_assoc($result))
		{		
			$qkm_optimised = $qkm_optimised + (float)$row["quantity"] * (float)$row["distance"];
			if(isset($row['new_id_admin']) && ($row['new_id_admin']!=null or $row['new_id_admin']!="")){
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(isset($row['new_id_district']) && ($row['new_id_district']!=null or $row['new_id_district']!="") and isset($row['approve_admin']) && $row['approve_admin']=="yes"){
				$row["distance"] = $row['new_distance_district'];
			}		
			$allocation = $allocation + (float)$row["quantity"];
			$qkm = $qkm + (float)$row["quantity"] * (float)$row["distance"];
		}
	}
}
$averagedistanceoptimised = ($allocation > 0) ? round($qkm_optimised/$allocation,2) : 0;
$qkm = round($qkm,2);


$allocation1 = 0;
$qkm1 = 0;
$qkm_optimised1 = 0;
$averagedistanceoptimised1 = 0;

if(!empty($leg_id)){
	$query = "SELECT * FROM optimised_table_leg1 WHERE id='$leg_id'";
	$result = mysqli_query($con,$query);
	if($result && mysqli_num_rows($result)>0){
		while($row=mysqli_fetch_assoc($result)){
			$cost1 = $row["cost"];
		}
	}
	
	if(!empty($tablename1)){
		$query = "SELECT * FROM $tablename1 WHERE 1";
		$result = mysqli_query($con,$query);
		if($result){
			while($row = mysqli_fetch_assoc($result))
			{		
				$qkm_optimised1 = $qkm_optimised1 + (float)$row["quantity"] * (float)$row["distance"];
				if(isset($row['new_id_admin']) && ($row['new_id_admin']!=null or $row['new_id_admin']!="")){
					$row["distance"] = $row['new_distance_admin'];
				}
				else if(isset($row['new_id_district']) && ($row['new_id_district']!=null or $row['new_id_district']!="") and isset($row['approve_admin']) && $row['approve_admin']=="yes"){
					$row["distance"] = $row['new_distance_district'];
				}		
				$allocation1 = $allocation1 + (float)$row["quantity"];
				$qkm1 = $qkm1 + (float)$row["quantity"] * (float)$row["distance"];
			}
		}
	}
	$averagedistanceoptimised1 = ($allocation1 > 0) ? round($qkm1/$allocation1,2) : 0;
	$qkm1 = round($qkm1,2);
	
}

$data = null;
$data1 = null;

if(!empty($tablename)){
	$query = "SELECT * FROM ".$tablename." WHERE 1";
	$result = mysqli_query($con,$query);
	if($result){
		while($row = mysqli_fetch_array($result))
		{
			
			if(isset($row['new_id_admin']) && ($row['new_id_admin']!=null or $row['new_id_admin']!="")){
				$id_wh = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id_wh'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_admin'];
				$row["from_name"] = $row['new_name_admin'];
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(isset($row['new_id_district']) && ($row['new_id_district']!=null or $row['new_id_district']!="") and isset($row['approve_admin']) && $row['approve_admin']=="yes"){
			
				$id_wh = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id_wh'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_district'];
				$row["from_name"] = $row['new_name_district'];
				$row["distance"] = $row['new_distance_district'];
			}
			$data[] = $row;

		}
	}
}

if(!empty($tablename1) && $tablename1 != $tablename){
	$query = "SELECT * FROM ".$tablename1." WHERE 1";
	$result = mysqli_query($con,$query);
	if($result){
		while($row = mysqli_fetch_array($result))
		{
			if(isset($row['new_id_admin']) && ($row['new_id_admin']!=null or $row['new_id_admin']!="")){
				$id_wh = $row['new_id_admin'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id_wh'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_admin'];
				$row["from_name"] = $row['new_name_admin'];
				$row["distance"] = $row['new_distance_admin'];
			}
			else if(isset($row['new_id_district']) && ($row['new_id_district']!=null or $row['new_id_district']!="") and isset($row['approve_admin']) && $row['approve_admin']=="yes"){
			
				$id_wh = $row['new_id_district'];
				$query_warehouse = "SELECT latitude,longitude,district FROM warehouse WHERE id='$id_wh'";
				$result_warehouse = mysqli_query($con,$query_warehouse);
				if($result_warehouse && mysqli_num_rows($result_warehouse)!=0){
					$row_warehouse = mysqli_fetch_assoc($result_warehouse);
					$row["from_lat"] = $row_warehouse['latitude'];
					$row["from_long"] = $row_warehouse['longitude'];
					$row["from_district"] = $row_warehouse['district'];
				}
				$row["from_id"] = $row['new_id_district'];
				$row["from_name"] = $row['new_name_district'];
				$row["distance"] = $row['new_distance_district'];
			}
			$data1[] = $row;
		}
	}
}

$tableData_pdf = array();
array_push($tableData_pdf,$columns_pdf);

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
		$i = $i + 1;
		if($i==10){
			$colWidth = $colWidth*3;
		}else{
			$colWidth = $originalColWidth;
		}
		$fontSize = 12;
		$pdf->SetFont('Arial', 'B', $fontSize);
		// Reduce font size if text is too wide for the cell
		while ($pdf->GetStringWidth($col) > $colWidth - 2 && $fontSize > 1) {
			$fontSize -= 1;
			$pdf->SetFont('Arial', 'B', $fontSize);
		}
		$pdf->Cell($colWidth, 10, $col, 1, 0, 'C', true);
	}
	$pdf->Ln();
}

// Add to lines
$fontSize = 12;
$pdf->SetFont('Arial', 'B', $fontSize);
$text = "PDS report generated for state Tamil Nadu and applicable month ".ucfirst($month)." and Date ".$date;
$pdf->Cell(0, 10, $text, 0, 1);

$text = "Cost saving for L2";
$pdf->Cell(0, 10, $text, 0, 1);

$pdf->Cell(40, 10, 'Qkm', 1);
$pdf->Cell(40, 10, 'Allocation', 1);
$pdf->Cell(50, 10, 'Average Distance', 1);
$pdf->Cell(40, 10, 'Cost', 1);
$pdf->Ln();


$pdf->Cell(40, 10, $qkm, 1);
$pdf->Cell(40, 10, $allocation, 1);
$pdf->Cell(50, 10, $averagedistanceoptimised, 1);
$pdf->Cell(40, 10, $cost, 1);
$pdf->Ln();

if(!empty($tablename1) && $tablename1 != $tablename){
	$text = "Cost saving for L1";
	$pdf->Cell(0, 10, $text, 0, 1);

	$pdf->Cell(40, 10, 'Qkm', 1);
	$pdf->Cell(40, 10, 'Allocation', 1);
	$pdf->Cell(50, 10, 'Average Distance', 1);
	$pdf->Cell(40, 10, 'Cost', 1);
	$pdf->Ln();

	$pdf->Cell(40, 10, $qkm1, 1);
	$pdf->Cell(40, 10, $allocation1, 1);
	$pdf->Cell(50, 10, $averagedistanceoptimised1, 1);
	$pdf->Cell(40, 10, $cost1, 1);
	$pdf->Ln();
}
$pdf->Ln();
// Add the header
addRow($pdf, $tableData_pdf[0], $colWidth, true);

// Add the data rows
$rowHeight = 10;
$maxRowsPerPage = ($pdf->GetPageHeight() - 20) / $rowHeight; // Subtract margins (10 mm each top and bottom)

if($data!=null){
	for ($i = 0; $i < count($data); $i++) {
		if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) { // Check if we need to add a new page
			$pdf->AddPage();
			addRow($pdf, $tableData_pdf[0], $colWidth, true); // Add the header again on the new page
		}
		$temp = array();
		
		for($j=0;$j<count($data[$i]);$j++){
			$temp["scenario"] = $data[$i]["scenario"];
			$temp["from"] = $data[$i]["from"];
			$temp["from_state"] = $data[$i]["from_state"];
			$temp["from_id"] = $data[$i]["from_id"];
			$temp["from_name"] = $data[$i]["from_name"];
			$temp["from_district"] = $data[$i]["from_district"];
			$temp["from_lat"] = $data[$i]["from_lat"];
			$temp["from_long"] = $data[$i]["from_long"];
			$temp["to"] = $data[$i]["to"];
			$temp["to_state"] = $data[$i]["to_state"];
			$temp["to_id"] = $data[$i]["to_id"];
			$temp["to_name"] = $data[$i]["to_name"];
			$temp["to_district"] = $data[$i]["to_district"];
			$temp["to_lat"] = $data[$i]["to_lat"];
			$temp["to_long"] = $data[$i]["to_long"];
			$temp["commodity"] = $data[$i]["commodity"];
			$temp["quantity"] = $data[$i]["quantity"];
			$temp["distance"] = $data[$i]["distance"];
			$temp["status"] = $data[$i]["status"];
		}
		addRow($pdf, $temp, $colWidth);
	}
}

if($data1!=null){
	for ($i = 0; $i < count($data1); $i++) {
		if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) { // Check if we need to add a new page
			$pdf->AddPage();
			addRow($pdf, $tableData_pdf[0], $colWidth, true); // Add the header again on the new page
		}
		$temp = array();
		for($j=0;$j<count($data1[$i]);$j++){
			$temp["scenario"] = $data1[$i]["scenario"];
			$temp["from"] = $data1[$i]["from"];
			$temp["from_state"] = $data1[$i]["from_state"];
			$temp["from_id"] = $data1[$i]["from_id"];
			$temp["from_name"] = $data1[$i]["from_name"];
			$temp["from_district"] = $data1[$i]["from_district"];
			$temp["from_lat"] = $data1[$i]["from_lat"];
			$temp["from_long"] = $data1[$i]["from_long"];
			$temp["to"] = $data1[$i]["to"];
			$temp["to_state"] = $data1[$i]["to_state"];
			$temp["to_id"] = $data1[$i]["to_id"];
			$temp["to_name"] = $data1[$i]["to_name"];
			$temp["to_district"] = $data1[$i]["to_district"];
			$temp["to_lat"] = $data1[$i]["to_lat"];
			$temp["to_long"] = $data1[$i]["to_long"];
			$temp["commodity"] = $data1[$i]["commodity"];
			$temp["quantity"] = $data1[$i]["quantity"];
			$temp["distance"] = $data1[$i]["distance"];
			$temp["status"] = $data1[$i]["status"];
		}
		addRow($pdf, $temp, $colWidth);
	}
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
echo $pdf->Output('S');
//exit();
?>