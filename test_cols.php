<?php
require('c:/xampp/htdocs/TamilNadu_distribution/pds_dfpd_tamilnadu/util/Connection.php');

function mapRow($row) {
    // If table has swapped columns (e.g. Allocation_Wheat holds latitude)
    if (isset($row["Allocation_Wheat"]) && is_numeric($row["Allocation_Wheat"]) && $row["Allocation_Wheat"] < 40) {
        $lat = $row["Allocation_Wheat"];
        $lng = isset($row["Allocation_Rice"]) ? $row["Allocation_Rice"] : '';
        $dWheat = isset($row["Allocation_FRice"]) ? $row["Allocation_FRice"] : '0';
        $dRice = isset($row["latitude"]) ? $row["latitude"] : '0';
        $dFRice = isset($row["demand_frice"]) ? $row["demand_frice"] : '0';
    } else {
        $lat = isset($row["latitude"]) ? $row["latitude"] : '';
        $lng = isset($row["longitude"]) ? $row["longitude"] : '';
        $dWheat = isset($row["demand"]) ? $row["demand"] : (isset($row["Allocation_Wheat"]) ? $row["Allocation_Wheat"] : '0');
        $dRice = isset($row["demand_rice"]) ? $row["demand_rice"] : (isset($row["Allocation_Rice"]) ? $row["Allocation_Rice"] : '0');
        $dFRice = isset($row["demand_frice"]) ? $row["demand_frice"] : (isset($row["Allocation_FRice"]) ? $row["Allocation_FRice"] : '0');
    }
    return array(
        'lat' => $lat,
        'lng' => $lng,
        'demand' => $dWheat,
        'demand_rice' => $dRice,
        'demand_frice' => $dFRice
    );
}

echo "Mapped row from `fps_a9ppjxrfvezmud`:\n";
$r1 = mysqli_query($con, "SELECT * FROM `fps_a9ppjxrfvezmud` WHERE id='01HP020PY' LIMIT 1");
print_r(mapRow(mysqli_fetch_assoc($r1)));

echo "\nMapped row from `fps_lbqu5zshdjte0r`:\n";
$r2 = mysqli_query($con, "SELECT * FROM `fps_lbqu5zshdjte0r` WHERE id='01HP020PY' LIMIT 1");
print_r(mapRow(mysqli_fetch_assoc($r2)));
?>
