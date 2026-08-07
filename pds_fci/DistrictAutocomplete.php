<?php

require('util/Connection.php');

 ?>

<script>

var x = document.getElementById("district");

<?php
$query = "SELECT * FROM districts ORDER BY name";
$result = mysqli_query($con,$query);
$numrows = mysqli_num_rows($result);

while($row = mysqli_fetch_assoc($result)){
	echo 'var option = document.createElement("option");';
	echo 'option.text = "'.strtoupper($row['name']).'";';
	echo 'option.value = "'.strtoupper($row['name']).'";';
	echo 'x.add(option);';
}

?>
</script>