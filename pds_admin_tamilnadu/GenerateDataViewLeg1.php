<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require 'vendor/autoload.php';
require('api/fpdf/fpdf.php');

$columns_pdf = ["scenario","from","from_state","from_id","from_name","from_district","from_lat","from_long","to","to_state","to_id","to_name","to_district","to_lat","to_long","commodity","quantity","distance","status"];

$filename = 'table_data_leg1';

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
if (empty($id)) {
    $query = "SELECT * FROM optimised_table_leg1 ORDER BY last_updated DESC LIMIT 1";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id = $row["id"];
    }
}

$tablename = get_safe_optimised_table($con, "optimiseddata_leg1_", $id);
if (empty($tablename)) {
    $tablename = get_safe_optimised_table($con, "optimiseddata_", $id);
}
if (empty($tablename)) {
    $tablename = "optimiseddata_leg1";
}

$month = "";
$date = "";
$cost = "";

if(!empty($id)){
	$query = "SELECT * FROM optimised_table_leg1 WHERE id='$id'";
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

$data = null;

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

$tableData_pdf = array();
array_push($tableData_pdf, $columns_pdf);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 15);

$pageWidth = $pdf->GetPageWidth() - 20;
$numCols = count($tableData_pdf[0]) + 2;
$colWidth = $pageWidth / $numCols;
$originalColWidth = $colWidth;

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
		while ($pdf->GetStringWidth($col) > $colWidth - 2 && $fontSize > 1) {
			$fontSize -= 1;
			$pdf->SetFont('Arial', 'B', $fontSize);
		}
		$pdf->Cell($colWidth, 10, $col, 1, 0, 'C', true);
	}
	$pdf->Ln();
}

$fontSize = 12;
$pdf->SetFont('Arial', 'B', $fontSize);
$text = "PDS report generated for state Tamil Nadu and applicable month ".ucfirst($month)." and Date ".$date;
$pdf->Cell(0, 10, $text, 0, 1);

$text = "Cost saving for L1";
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
$pdf->Ln();

addRow($pdf, $tableData_pdf[0], $colWidth, true);

$rowHeight = 10;

if ($data != null) {
	for ($i = 0; $i < count($data); $i++) {
		if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) {
			$pdf->AddPage();
			addRow($pdf, $tableData_pdf[0], $colWidth, true);
		}
		$temp = array();
		for ($j = 0; $j < count($data[$i]); $j++) {
			$temp["scenario"] = isset($data[$i]["scenario"]) ? $data[$i]["scenario"] : '';
			$temp["from"] = isset($data[$i]["from"]) ? $data[$i]["from"] : '';
			$temp["from_state"] = isset($data[$i]["from_state"]) ? $data[$i]["from_state"] : '';
			$temp["from_id"] = isset($data[$i]["from_id"]) ? $data[$i]["from_id"] : '';
			$temp["from_name"] = isset($data[$i]["from_name"]) ? $data[$i]["from_name"] : '';
			$temp["from_district"] = isset($data[$i]["from_district"]) ? $data[$i]["from_district"] : '';
			$temp["from_lat"] = isset($data[$i]["from_lat"]) ? $data[$i]["from_lat"] : '';
			$temp["from_long"] = isset($data[$i]["from_long"]) ? $data[$i]["from_long"] : '';
			$temp["to"] = isset($data[$i]["to"]) ? $data[$i]["to"] : '';
			$temp["to_state"] = isset($data[$i]["to_state"]) ? $data[$i]["to_state"] : '';
			$temp["to_id"] = isset($data[$i]["to_id"]) ? $data[$i]["to_id"] : '';
			$temp["to_name"] = isset($data[$i]["to_name"]) ? $data[$i]["to_name"] : '';
			$temp["to_district"] = isset($data[$i]["to_district"]) ? $data[$i]["to_district"] : '';
			$temp["to_lat"] = isset($data[$i]["to_lat"]) ? $data[$i]["to_lat"] : '';
			$temp["to_long"] = isset($data[$i]["to_long"]) ? $data[$i]["to_long"] : '';
			$temp["commodity"] = isset($data[$i]["commodity"]) ? $data[$i]["commodity"] : '';
			$temp["quantity"] = isset($data[$i]["quantity"]) ? $data[$i]["quantity"] : '';
			$temp["distance"] = isset($data[$i]["distance"]) ? $data[$i]["distance"] : '';
			$temp["status"] = isset($data[$i]["status"]) ? $data[$i]["status"] : '';
		}
		addRow($pdf, $temp, $colWidth);
	}
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
echo $pdf->Output('S');
?>
