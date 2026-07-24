<?php
require('util/Connection.php');
require('util/SessionCheck.php');
require('Header.php');

?>

 <style>
        /* Increase the font size for the entire page */
        body {
            font-size: 16px; /* Change this value to increase or decrease the base font size */
        }

        /* Increase the font size for specific elements */
        .breadcrumb,
        .panel-title,
        .btn {
            font-size: 12px; /* Adjust the font size for breadcrumbs, panel titles, and buttons */
        }

        /* Increase font size for tables */
        table,
        th,
        td {
            font-size: 15px; /* Font size for table elements */
        }

        /* Increase the font size for form labels, inputs, and buttons */
        label,
        input,
        button {
            font-size: 12px; /* Font size for form elements and buttons */
        }

        /* Increase the font size for specific elements within the page */
        .popup,
        .help-block {
            font-size: 16px; /* Font size for popup elements and help blocks */
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
                                    <h3 class="panel-title">Tamil Nadu Leg1 Data</h3> 
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
										
										$query = "SELECT * FROM optimised_table_leg1 ORDER BY last_updated ASC";
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
			post({url: "WarehouseView.php", id: temp_id, step: "leg1"});
		}
		
		function fci_open(temp_id){
			post({url: "DCPView.php", id: temp_id});
		}
		
		function optimised_open(temp_id){
			post({url: "OptimisedDataViewLeg1.php", id: temp_id});
		}
		
		function generate_report(temp_id){
			post({url: "GenerateDataView.php", id: temp_id, step: "leg1"});
		}
		</script>	
    </body>
</html>
