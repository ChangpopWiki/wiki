<?php

$filter = $_GET['filter'] ?? null;

$cmd = ['php', "/var/www/html/maintenance/run.php", "dumpBackup", '--current'];

if ($filter) {
    $cmd[] = '--filter=' . $filter;
}

$process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
http_response_code(200);
header('Content-Type: application/xml');
fpassthru($pipes[1]);
proc_close($process);