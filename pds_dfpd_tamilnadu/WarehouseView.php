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

function get_safe_table_name($con, $prefix, $id_val) {
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

$tablename = get_safe_table_name($con, "warehouse_", $id);
if (empty($tablename)) {
    $tablename = get_safe_table_name($con, "warehouse_leg1_", $id);
}
if (empty($tablename)) {
    $tablename = "warehouse";
}

?>
<style>
     td {
            font-size: 16px;
        }
        .table thead tr th {
    background-color: #95b75d !important;
    color: black;
}
    </style>

                <!-- START BREADCRUMB -->
                <ul class="breadcrumb">
                    <li><a href="Warehouse.php">Home</a></li>
                    <li class="active">Warehouse View</li>
                </ul>
                <!-- END BREADCRUMB -->


				<!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">

                    <div class="row">
                        <div class="col-md-12">

                            <!-- START SIMPLE DATATABLE -->
                            <div class="panel panel-default">
							<div class="panel-heading">
                                    <h3 class="panel-title">Warehouse</h3>
                                </div>
								<div style="float:right" style="margin:10px">
									<button id="downloadCSV" class="btn btn-warning" style="margin-bottom: 10px;" type="button">Download CSV</button>
									<button id="downloadXLSX" class="btn btn-success" style="margin-bottom: 10px;" type="button">Download XLSX</button>
								</div>
                                <div class="panel-body">
                                 <div class="table-responsive">
                                    <table id="export_table" class="table datatable">
                                        <thead>
                                            <tr>
												<th style="font-size:15px">District</th>
												<th style="font-size:15px">Name of Warehouse</th>
												<th style="font-size:15px">Warehouse ID</th> 
												<th style="font-size:15px">Motorable/Non-Motorable</th>
												<th style="font-size:15px">Warehouse Type</th>
												<th style="font-size:15px">Latitude</th>
												<th style="font-size:15px">Longitude</th>
												<th style="font-size:15px">Storage(Qtl)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
										<?php
										
										$chk = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tablename) . "'");
										if ($chk && mysqli_num_rows($chk) > 0) {
											$query = "SELECT * FROM " . mysqli_real_escape_string($con, $tablename) . " WHERE 1";
											$result = mysqli_query($con, $query);
											if ($result) {
												while ($row = mysqli_fetch_array($result)) {
													$t = isset($row['type']) ? $row['type'] : '';
													$wt = isset($row['warehousetype']) ? $row['warehousetype'] : '';
													echo "<tr><td>{$row['district']}</td>" .
													"<td>{$row['name']}</td>" .
													"<td>{$row['id']}</td>" .
													"<td>{$t}</td>" .
													"<td>{$wt}</td>" .
													"<td>{$row['latitude']}</td>" .
													"<td>{$row['longitude']}</td>" .
													"<td>{$row['storage']}</td></tr>";
												}
											}
										}
										
										?>
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

		<script>
		function getDateString(){
			var currentDate = new Date();
			var year = currentDate.getFullYear();
			var month = currentDate.getMonth() + 1;
			var day = currentDate.getDate();
			var str = year + "-" + month + "-" + day;
			return str;
		}
		
		document.getElementById('downloadCSV').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				const csvResponse = await fetch('api/DownloadOptimalDataWarehouse.php?format=csv&tableName='+tableName);
				const csvBlob = await csvResponse.blob();
				downloadFile(csvBlob, 'Warehouse_' + getDateString() + '.csv');
			} catch (error) {
				console.error('Error downloading CSV file:', error);
			}
		});

		document.getElementById('downloadXLSX').addEventListener('click', async function() {
			try {
				var tableName = '<?php echo $tablename ?>';
				const excelResponse = await fetch('api/DownloadOptimalDataWarehouse.php?format=xlsx&tableName='+tableName);
				const excelBlob = await excelResponse.blob();
				downloadFile(excelBlob, 'Warehouse_' + getDateString() + '.xlsx');
			} catch (error) {
				console.error('Error downloading XLSX file:', error);
			}
		});
		
		function downloadFile(blob, fileName) {
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = fileName;
			link.click();
			window.URL.revokeObjectURL(url);
		}
		</script>
    </body>
</html>
