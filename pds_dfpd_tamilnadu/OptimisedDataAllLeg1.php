<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require('Header.php');

$selectedYear = isset($_GET['year']) ? $_GET['year'] : 'all';

$yearQuery = "SELECT DISTINCT year FROM optimised_table_leg1 WHERE year IS NOT NULL AND year != '' ORDER BY year DESC";
$yearResult = mysqli_query($con, $yearQuery);
$availableYears = array();
if ($yearResult) {
    while ($yRow = mysqli_fetch_assoc($yearResult)) {
        $availableYears[] = $yRow['year'];
    }
}

?>

 <style>
        body {
            font-size: 16px;
        }

        .breadcrumb,
        .panel-title,
        .btn {
            font-size: 12px;
        }

        table,
        th,
        td {
            font-size: 15px;
        }

        label,
        input,
        button {
            font-size: 12px;
        }

        .popup,
        .help-block {
            font-size: 16px;
        }
    </style>
              
                <!-- START BREADCRUMB -->
                <ul class="breadcrumb">
                    <li><a href="#">Home</a></li>                    
                    <li class="active">Optimised Leg1 Data View</li>
                </ul>
                <!-- END BREADCRUMB -->                       
                
				
				<!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">                
                
                    <div class="row">
                        <div class="col-md-12">

                            <!-- START SIMPLE DATATABLE -->
                            <div class="panel panel-default">
								<div class="panel-heading">                                
                                    <h3 class="panel-title" style="margin-top:6px;">Tamil Nadu Leg1 Data</h3> 
                                    <div style="float:right; margin-right:15px;">
                                        <label style="margin-right:8px; font-weight:bold;">Select Year:</label>
                                        <select id="yearFilter" class="form-control" style="width:140px; display:inline-block;" onchange="filterByYear(this.value)">
                                            <option value="all" <?php if($selectedYear == 'all') echo 'selected'; ?>>All Years</option>
                                            <?php foreach($availableYears as $y): ?>
                                                <option value="<?php echo htmlspecialchars($y); ?>" <?php if($selectedYear == $y) echo 'selected'; ?>><?php echo htmlspecialchars($y); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
								<div class="panel-body">
                                 <div class="table-responsive">
                                     <table id="export_table" class="table" style="text-align: center;">
                                        <thead>
                                            <tr>
												<th style="font-size:16px; text-align: center; vertical-align: middle;">Year</th>
												<th style="font-size:16px; text-align: center; vertical-align: middle;">Month</th>
												<th style="font-size:16px; text-align: center; vertical-align: middle;">Applicable Month</th>
												<th style="font-size:16px; text-align: center; vertical-align: middle;">Warehouse</th>
												<th style="font-size:16px; text-align: center; vertical-align: middle;">FCI</th>
												<th style="font-size:16px; text-align: center; vertical-align: middle;">Optimised Data</th>
												<th style="font-size:16px; text-align: center; vertical-align: middle;">Generate Data</th>
                                            </tr>
                                        </thead>
                                        <tbody id="table_body">
										<?php
										
										if ($selectedYear != 'all' && !empty($selectedYear)) {
											$safeYear = mysqli_real_escape_string($con, $selectedYear);
											$query = "SELECT * FROM optimised_table_leg1 WHERE year = '$safeYear' ORDER BY last_updated ASC";
										} else {
											$query = "SELECT * FROM optimised_table_leg1 ORDER BY last_updated ASC";
										}
										$result = mysqli_query($con,$query);
										$numrows = mysqli_num_rows($result);
										while($row = mysqli_fetch_assoc($result))
										{
											$temp_id = (string)$row['id'];
											$app_month = (!empty($row['applicable'])) ? $row['applicable'] : $row['month'];
											echo "<tr>
											
											<td style='text-align: center; text-transform: capitalize;'>{$row['year']}</td>
											<td style='text-align: center; text-transform: capitalize;'>{$row['month']}</td>
											<td style='text-align: center; text-transform: capitalize;'>{$app_month}</td>
											<td style='text-align: center;'>
												<button class='btn btn-info btn-rounded' onclick=\"warehouse_open('{$temp_id}')\">View Warehouses</button>
											</td>
											<td style='text-align: center;'>
												<button class='btn btn-warning btn-rounded' onclick=\"fci_open('{$temp_id}')\">View FCI</button>
											</td>
											<td style='text-align: center;'>
												<button class='btn btn-danger btn-rounded' onclick=\"optimised_open('{$temp_id}')\">View Data</button>
											</td>
											<td style='text-align: center;'>
												<button class='btn btn-success btn-rounded' onclick=\"generate_report('{$temp_id}')\">View Report</button>
											</td>
											
											</tr>";
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
        <script type="text/javascript" src="js/plugins.js"></script>
        <script type="text/javascript" src="js/actions.js"></script>
        <!-- END PAGE PLUGINS -->

        <script>
		function post(params, method='post') {
			var form = document.createElement('form');
			form.method = method;
			form.action = params.url || '';

			for (var key in params) {
				if (params.hasOwnProperty(key) && key !== 'url') {
					var hiddenField = document.createElement('input');
					hiddenField.type = 'hidden';
					hiddenField.name = key;
					hiddenField.value = params[key];
					form.appendChild(hiddenField);
				}
			}
			document.body.appendChild(form);
			form.submit();
		}

		function warehouse_open(temp_id){
			post({url: "WarehouseViewLeg1.php", id: temp_id, step: "leg1"});
		}
		
		function fci_open(temp_id){
			post({url: "DCPView.php", id: temp_id});
		}
		
		function optimised_open(temp_id){
			post({url: "OptimisedDataViewLeg1.php", id: temp_id});
		}
		
		function generate_report(temp_id){
			post({url: "GenerateDataViewLeg1.php", id: temp_id, step: "leg1"});
		}

		function filterByYear(yearVal) {
			if (yearVal === 'all') {
				window.location.href = 'OptimisedDataAllLeg1.php';
			} else {
				window.location.href = 'OptimisedDataAllLeg1.php?year=' + encodeURIComponent(yearVal);
			}
		}
		</script>	
    </body>
</html>
