<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require('Header.php');

$id = "";
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
} else if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $query = "SELECT * FROM optimised_table ORDER BY last_updated DESC LIMIT 1";
    $result = mysqli_query($con, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $id = $row["id"];
    }
}
$id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
$id_lower = strtolower($id);

if (!empty($id)) {
    $chk1 = mysqli_query($con, "SHOW TABLES LIKE 'fps_" . mysqli_real_escape_string($con, $id) . "'");
    if ($chk1 && mysqli_num_rows($chk1) > 0) {
        $tablename = "fps_" . $id;
    } else {
        $chk2 = mysqli_query($con, "SHOW TABLES LIKE 'fps_" . mysqli_real_escape_string($con, $id_lower) . "'");
        if ($chk2 && mysqli_num_rows($chk2) > 0) {
            $tablename = "fps_" . $id_lower;
        } else {
            $tablename = "fps";
        }
    }
} else {
    $tablename = "fps";
}

?>
<style>
    td {
            font-size: 15px; /* Increase font size for table headers and data cells */
        }
        .table thead tr th {
    background-color: #95b75d !important;
    /* border: 2px solid #777; */
    color: black;
    /* Optional: Font size for table header */
}
</style>

                <!-- START BREADCRUMB -->
                <ul class="breadcrumb">
                    <li><a href="FPS.php">Home</a></li>
                    <li class="active">FPS View</li>
                </ul>
                <!-- END BREADCRUMB -->


				<!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">

                    <div class="row">
                        <div class="col-md-12">

                            <!-- START SIMPLE DATATABLE -->
                            <div class="panel panel-default">
							<div class="panel-heading">
                                    <h3 class="panel-title">FPS</h3>
                                </div>
								<div style="float:right" style="margin:10px">
									<button id="downloadCSV" class="btn btn-warning" style="margin-bottom: 10px;" type="button">Download CSV</button>
									<button id="downloadXLSX" class="btn btn-success" style="margin-bottom: 10px;" type="button">Download XLSX</button>
								</div>
								<div class="row" style="float:right;margin-top:20px">
									<div class="col-md-8">
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label class="col-md-3 control-label">Districts</label>
											<div class="col-md-9">  
												<div class="input-group">
												<span class="input-group-addon"><span class="fa fa-certificate"></span></span>						
												<select class="form-control" id="district" name="district" onchange="fetchDataFromServer()">
													<option value=''>Select</option>
												</select>
												</div>
											</div>
										</div>
									</div>
								</div>
                                <div class="panel-body">
                                 <div class="table-responsive">
                                    <table id="export_table" class="table">
                                        <thead>
                                            <tr>
												<th style="font-size:16px">District</th>
												<th style="font-size:16px">Name of FPS</th>
												<th style="font-size:16px">FPS ID</th>
												<th style="font-size:16px">Model FPS/Normal FPS</th>
												<th style="font-size:16px">Latitude</th>
												<th style="font-size:16px">Longitude</th>
												<th style="font-size:16px">Demand of Wheat(Qtl)</th>
												<th style="font-size:16px">Demand of Rice(Qtl)</th>
												<th style="font-size:16px">Demand of FRice(Qtl)</th>
                                            </tr>
                                        </thead>
										 <tbody id="fps_table">
										</tbody>
                                    </table>
                                  </div>
                                </div>
                            </div>
                            <!-- END SIMPLE DATATABLE -->

                        </div>
                    </div>

                </div>
                <!-- PAGE CONTENT WRAPPER -->
            </div>
            <!-- END PAGE CONTENT -->
        </div>
        <!-- END PAGE CONTAINER -->



    <!-- START SCRIPTS -->
        <!-- START PLUGINS -->
        <script type="text/javascript" src="js/plugins/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="js/plugins/jquery/jquery-ui.min.js"></script>
        <script type="text/javascript" src="js/plugins/bootstrap/bootstrap.min.js"></script>
        <!-- END PLUGINS -->

        <!-- THIS PAGE PLUGINS -->
        <script type='text/javascript' src='js/plugins/icheck/icheck.min.js'></script>
        <script type="text/javascript" src="js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
        <script type="text/javascript" src="js/plugins/datatables/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/tableExport.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jquery.base64.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/html2canvas.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/sprintf.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/jspdf.js"></script>
		<script type="text/javascript" src="js/plugins/tableexport/jspdf/libs/base64.js"></script>
        <script type="text/javascript" src="js/plugins.js"></script>
        <script type="text/javascript" src="js/actions.js"></script>
        <!-- END PAGE PLUGINS -->

        <!-- START TEMPLATE -->
        
		<?php  require('DistrictAutocomplete.php'); ?>
        <!-- END TEMPLATE -->
		<script>
		function getDateString(){
			var currentDate = new Date();
			var year = currentDate.getFullYear();
			var month = currentDate.getMonth() + 1; // Month is zero-based, so we add 1
			var day = currentDate.getDate();
			var str = year + "-" + month + "-" + day;
			return str;
		}
		
		document.getElementById('downloadCSV').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				var district = document.getElementById('district').value;
				const csvResponse = await fetch('api/DownloadOptimalDataFPS.php?format=csv&tableName=' + tableName + '&district=' + district);
				const csvBlob = await csvResponse.blob();
				downloadFile(csvBlob, 'Tamil Nadu_FPS_' + getDateString() + '.csv');
			} catch (error) {
				console.error('Error downloading CSV file:', error);
			}
		});

		// Event listener for downloading XLSX
		document.getElementById('downloadXLSX').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				var district = document.getElementById('district').value;
				const excelResponse = await fetch('api/DownloadOptimalDataFPS.php?format=xlsx&tableName=' + tableName + '&district=' + district);
				const excelBlob = await excelResponse.blob();
				downloadFile(excelBlob, 'Tamil Nadu_FPS_' + getDateString() + '.xlsx');
			} catch (error) {
				console.error('Error downloading XLSX file:', error);
			}
		});

		// Event listener for downloading PDF
		/*document.getElementById('downloadPDF').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';	
				const pdfResponse = await fetch('api/DownloadOptimalDataFPS.php?format=pdf&tableName='+tableName);
				const pdfBlob = await pdfResponse.blob();

				const url = window.URL.createObjectURL(pdfBlob);
				const link = document.createElement('a');
				link.href = url;
				link.download = 'Pb_Warehouse_' + getDateString() + '.pdf';
				link.click();
				window.URL.revokeObjectURL(url);
			} catch (error) {
				console.error('Error downloading PDF file:', error);
			}
		});*/
		
		// Functions for file download and PDF generation (similar to previous code)
		function downloadFile(blob, fileName) {
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = fileName;
			link.click();
			window.URL.revokeObjectURL(url);
		}
		
		
		
		function fetchDataFromServer(){
			var districtElement = document.getElementById('district');
			var district = districtElement.value;
			
			if(district==""){
				var options = districtElement.options;
				for (var i = 0; i < options.length; i++) {
					if (options[i].value != "all" && options[i].value != "") {
						districtElement.selectedIndex = i;
						district = options[i].value ;
						break;
					}
				}
			}
			
			var dataString = "district=" + district + "&tablename=" + '<?php echo $tablename ?>';
			
			
			$.ajax({
				type: "POST",
				url: "api/fetchFPSViewData.php",
				data: dataString,
				cache: false,
				error: function(){
					alert("timeout");
					$("#filter_button").attr("disabled",false);
				},
				timeout: 216000,
				success: function(result){
					//console.log(result);
					try{
						$('#fps_table').empty();
						var resultarray = JSON.parse(result);
						var obj = resultarray["data"];
						console.log(obj);
						for (var datafield in obj){
							var row = obj[datafield];
							var lat = (row["latitude"] !== undefined && row["latitude"] !== null) ? row["latitude"] : "";
							var lng = (row["longitude"] !== undefined && row["longitude"] !== null) ? row["longitude"] : "";
							var dWheat = (row["demand"] !== undefined && row["demand"] !== null && row["demand"] !== "") ? row["demand"] : "0";
							var dRice = (row["demand_rice"] !== undefined && row["demand_rice"] !== null && row["demand_rice"] !== "") ? row["demand_rice"] : "0";
							var dFRice = (row["demand_frice"] !== undefined && row["demand_frice"] !== null && row["demand_frice"] !== "") ? row["demand_frice"] : "0";

							if (row["Allocation_Wheat"] !== undefined && !isNaN(parseFloat(row["Allocation_Wheat"])) && parseFloat(row["Allocation_Wheat"]) < 40) {
								lat = row["Allocation_Wheat"];
								lng = (row["Allocation_Rice"] !== undefined && row["Allocation_Rice"] !== null) ? row["Allocation_Rice"] : "";
								dWheat = (row["Allocation_FRice"] !== undefined && row["Allocation_FRice"] !== null) ? row["Allocation_FRice"] : "0";
								dRice = (row["latitude"] !== undefined && row["latitude"] !== null) ? row["latitude"] : "0";
								dFRice = (row["demand_frice"] !== undefined && row["demand_frice"] !== null) ? row["demand_frice"] : "0";
							}

							var subpart = "<tr><td>" +  row["district"] +  "</td><td>"  + row["name"] +  "</td><td>"  + row["id"] +  "</td><td>"  + row["type"] +  "</td><td>"  + lat +  "</td><td>"  + lng +  "</td><td>"  + dWheat +"</td><td>"  + dRice + "</td><td>"  + dFRice +  "</td></tr>";
							$('#fps_table').append(subpart);
						}
					}
					catch (error) {
					}
				}
			});
		}
		
		fetchDataFromServer();
		
			
		</script>


    </body>
</html>
