<?php
// 프로젝트 내 모든 커스텀 특수 문서의 별칭을 한 곳에서 관리

$wgExtensionMessagesFiles['LocalSpecialPageAliases'] = __FILE__;

$specialPageAliases = [];

$specialPageAliases['en'] = [
    'MissingLyricPages' => ['MissingLyricPages'],
];

$specialPageAliases['ko'] = [
    'MissingLyricPages' => ['가사없는곡문서'],
];