<?php
$logBaseDir = __DIR__ . '/logs';
$cutoffDate = new DateTime('-180 days'); // Logs older than this will be deleted

function deleteOldLogs($dir, $cutoffDate) {
    echo "Cutoff Date: " . $cutoffDate->format('Y-m-d') . "\n";

    $yearDirs = glob($dir . '/*', GLOB_ONLYDIR);

    foreach ($yearDirs as $yearDir) {
        $monthDirs = glob($yearDir . '/*', GLOB_ONLYDIR);

        foreach ($monthDirs as $monthDir) {
            $logFiles = glob($monthDir . '/*.log');

            foreach ($logFiles as $logFile) {
                // Extract year, month, day
                $parts = explode('/', $logFile);
				$year  = basename($parts[count($parts) - 3]);
                $month = basename($parts[count($parts) - 2]);
                $day   = basename(basename($parts[count($parts) - 1]),'.log');
				
				
                $logDate = DateTime::createFromFormat('Y-m-d', "$year-$month-$day");
				if ($logDate && $logDate < $cutoffDate) {
                    echo "Deleting log: $logFile (" . $logDate->format('Y-m-d') . ")\n";
                    unlink($logFile);
                }
            }

            if (is_dir_empty($monthDir)) {
                echo "Removing empty month folder: $monthDir\n";
                rmdir($monthDir);
            }
        }

        if (is_dir_empty($yearDir)) {
            echo "Removing empty year folder: $yearDir\n";
            rmdir($yearDir);
        }
    }
}

function is_dir_empty($dir) {
    return is_readable($dir) && count(scandir($dir)) === 2;
}

deleteOldLogs($logBaseDir, $cutoffDate);

?>