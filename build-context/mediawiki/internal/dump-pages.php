<?php

$filter = $_GET['filter'] ?? null;

$cmd = ['php', "/var/www/html/maintenance/run.php", "dumpBackup", '--current'];

if ($filter) {
    $cmd[] = '--filter=' . $filter;
}

// 덤프가 중간에 실패했을 때 200 응답과 잘린 XML이 백업되지 않도록 임시 파일에 먼저 받은 뒤 종료 코드를 확인합니다.
// stderr(진행 상황 출력)도 파일로 받아, 읽지 않는 파이프 버퍼가 가득 차 덤프가 멈추는 일을 방지합니다.
$outFile = tempnam(sys_get_temp_dir(), 'dump-out-');
$errFile = tempnam(sys_get_temp_dir(), 'dump-err-');

try {
    $process = proc_open($cmd, [1 => ['file', $outFile, 'w'], 2 => ['file', $errFile, 'w']], $pipes);
    $exitCode = $process === false ? -1 : proc_close($process);
    clearstatcache(true, $outFile);

    if ($exitCode === 0) {
        http_response_code(200);
        header('Content-Type: application/xml');
        header('Content-Length: ' . filesize($outFile));
        readfile($outFile);
    } else {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "dumpBackup 실패 (exit code: {$exitCode})\n";
        readfile($errFile);
    }
} finally {
    unlink($outFile);
    unlink($errFile);
}
