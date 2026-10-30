<?php
# 개발 환경 디버그 설정 (MW_DEBUG 환경변수가 설정된 경우에만 불러옴)

error_reporting( -1 );

$wgDevelopmentWarnings = true;
$wgShowExceptionDetails = true;
$wgDebugToolbar = true;