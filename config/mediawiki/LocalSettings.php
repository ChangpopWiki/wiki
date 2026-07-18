<?php
# 창팝위키 LocalSettings.php
#
# See includes/MainConfigSchema.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}
## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename = "창팝위키";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";
$wgArticlePath = "/$1";
$wgUsePathInfo = true;
$wgMainPageIsDomainRoot = true;

## 짧은 action URL 설정
$actions = ['edit', 'watch', 'unwatch', 'delete', 'revert', 'rollback', 'protect', 'unprotect', 'markpatrolled', 'render', 'submit', 'history', 'purge', 'info', 'raw', 'pagevalues',];

foreach ( $actions as $action ) {
  $wgActionPaths[$action] = "/$1/+/$action";
}

## The protocol and server name to use in fully-qualified URLs
$wgServer = getenv('WG_SERVER') ?: 'http://localhost:10597';

## 내부 서버 주소
$wgInternalServer = 'http://localhost';

## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

## The URL paths to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogos = [
	'icon' => "$wgResourceBasePath/resources/assets/logo.svg",
];

$wgFavicon = "$wgResourceBasePath/resources/assets/logo.svg";

## 이메일 설정
## UPO(User Preference Option)는 사용자가 따로 조정 가능한 설정임을 의미합니다.
$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO

$wgPasswordSender = "noreply@changpop.wiki";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

$wgSMTP = [
    'host' => 'ssl://smtp.resend.com',
    'IDHost' => 'changpop.wiki',
    'port' => 465,
    'username' => 'resend',
    'password' => getenv('SMTP_PASSWORD'),
    'auth' => true
];

## 데이터베이스 설정
$wgDBtype = "mysql";
$wgDBserver = "database";
$wgDBname = "changpopwiki";
$wgDBuser =  "wikiuser";
$wgDBpassword = getenv('MARIADB_PASSWORD');

# MySQL specific settings
$wgDBprefix = "";
$wgDBssl = false;

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Shared database table
# This has no effect unless $wgSharedDB is also set.
$wgSharedTables[] = "actor";

## Shared memory settings
$wgMainCacheType = CACHE_ACCEL;
$wgMemCachedServers = [];

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

# 일반 사용자 업로드 불가
$wgGroupPermissions['user']['upload'] = false;
$wgGroupPermissions['user']['reupload'] = false;

# 업로드 파일 용량 제한
$wgUploadSizeWarning = 2 * 1024 * 1024;
$wgMaxUploadSize = 2 * 1024 * 1024;

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = false;

# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = true;

# 사이트 언어
$wgLanguageCode = "ko";

# 시간대
$wgLocaltimezone = "Asia/Seoul";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publicly accessible from the web.
$wgCacheDirectory = "$IP/cache";

$wgSecretKey = getenv('WG_SECRET_KEY');

# 이 값을 바꾸면 존재하는 모든 세션이 로그아웃됩니다.
$wgAuthenticationTokenVersion = "1";

# 저작권 페이지
$wgRightsPage = "Project:저작권";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

## Default skin: you can change the default skin. Use the internal symbolic
## names, e.g. 'vector' or 'monobook':
$wgDefaultSkin = "citizen";

## 스킨 및 확장 불러오기 부분은 별도의 설치 스크립트에 따라 자동으로 생성되도록 하였습니다.
## 사용하는 스킨 및 확장 목록은 여기 대신 extensions.toml을 참고하세요.
require_once "$IP/extensions-loader/ExtensionsLoader.php";

# 예외: 하위 확장인 ConfirmEdit/QuestyCaptcha는 스크립트가 감지할 수 없어 별도로 불러와야 함
wfLoadExtension('ConfirmEdit/QuestyCaptcha');

### --- 스킨 관련 설정 ---

# 제한된 페이지에서도 스킨 및 CSS 적용 허용
$wgAllowSiteCSSOnRestrictedPages = true;

## Vector 스킨 설정
# 반응형으로 만들기
$wgVectorResponsive = true;

## Citizen 스킨 설정 
# ShortDescription에서 제공하는 설명을 사용
$wgCitizenSearchDescriptionSource = 'wikidata';

## --- 이름공간 관련 설정 ---

# 기본 이름공간에서 하위 페이지 허용
$wgNamespacesWithSubpages[NS_MAIN] = true;

define("NS_FORM", 106);
define("NS_FORM_TALK", 107);
$wgExtraNamespaces[NS_FORM] = "양식";
$wgExtraNamespaces[NS_FORM_TALK] = "양식토론";
$wgNamespacesWithSubpages[NS_FORM] = true;
$wgNamespacesWithSubpages[NS_FORM_TALK] = true;

define("NS_DOC", 3010);
define("NS_DOC_TALK", 3011);
$wgExtraNamespaces[NS_DOC] = "설명문서";
$wgExtraNamespaces[NS_DOC_TALK] = "설명문서토론";
$wgNamespacesWithSubpages[NS_DOC] = true;
$wgNamespacesWithSubpages[NS_DOC_TALK] = true;

define("NS_EXAMPLE", 3106);
define("NS_EXAMPLE_TALK", 3107);
$wgExtraNamespaces[NS_EXAMPLE] = "본보기";
$wgExtraNamespaces[NS_EXAMPLE_TALK] = "본보기토론";
$wgNamespacesWithSubpages[NS_EXAMPLE] = true;
$wgNamespacesWithSubpages[NS_EXAMPLE_TALK] = true;

define("NS_LYRIC", 3200);
define("NS_LYRIC_TALK", 3201);
$wgExtraNamespaces[NS_LYRIC] = "가사";
$wgExtraNamespaces[NS_LYRIC_TALK] = "가사토론";
$wgNamespacesWithSubpages[NS_LYRIC] = true;
$wgNamespacesWithSubpages[NS_LYRIC_TALK] = true;
$wgNamespaceContentModels[NS_LYRIC] = CONTENT_MODEL_TEXT;

define("NS_TEMPLATESTYLE", 3810);
define("NS_TEMPLATESTYLE_TALK", 3811);
$wgExtraNamespaces[NS_TEMPLATESTYLE] = "틀스타일";
$wgExtraNamespaces[NS_TEMPLATESTYLE_TALK] = "틀스타일토론";
$wgNamespacesWithSubpages[NS_TEMPLATESTYLE] = true;
$wgNamespacesWithSubpages[NS_TEMPLATESTYLE_TALK] = true;
$wgNamespaceContentModels[NS_TEMPLATESTYLE] = "sanitized-css";

# 이름공간 별칭
$wgNamespaceAliases = [
	'창' => NS_PROJECT, # 창팝위키
	'사' => NS_USER, # 사용자
	'폼' => NS_FORM, # 양식
	'예' => NS_EXAMPLE, # 본보기
	'분' => NS_CATEGORY, # 분류
	'MW' => NS_MEDIAWIKI # 미디어위키
];


# --- 권한 및 그룹 관련 설정 ---

# 양식 이름공간 편집을 인터페이스 관리자로 제한
$wgNamespaceProtection[NS_FORM] = ['editinterface'];

# 미디어위키에서 기본적으로 정의된 그룹 제거
# * 참고: 미디어위키 사이트의 매뉴얼에 있는 방법은 현재 문제가 있다고 함. 이슈에 따라 MediaWikiServices 훅을 사용하는 방법을 사용.
# * 관련 링크:
#     https://www.mediawiki.org/wiki/Manual:User_rights#Removing_predefined_groups
#     https://phabricator.wikimedia.org/T275334
$wgHooks['MediaWikiServices'][] = static function (): void {
    global $wgGroupPermissions, $wgRevokePermissions, $wgAddGroups, $wgRemoveGroups, $wgGroupsAddToSelf, $wgGroupsRemoveFromSelf;
    
    # 제거할 그룹 목록
    $groupsToRemove = ['bureaucrat']; # 사무관 그룹 제거

    foreach ( $groupsToRemove as $group ) {
        unset( $wgGroupPermissions[$group] );
        unset( $wgRevokePermissions[$group] );
        unset( $wgAddGroups[$group] );
        unset( $wgRemoveGroups[$group] );
        unset( $wgGroupsAddToSelf[$group] );
        unset( $wgGroupsRemoveFromSelf[$group] );
    }
};

# 사무관 고유 권한 관리자로 이전
$wgGroupPermissions['sysop']['renameuser'] = true;
$wgGroupPermissions['sysop']['userrights'] = true;

# 자동인증된 사용자 조건
$wgAutoConfirmAge = 3600; # 1시간
$wgAutoConfirmCount = 1; # 1회 이상

# --- 그 외 설정 ---

# 외부 이미지 허용
$wgAllowExternalImages = true;
$wgAllowExternalImagesFrom = [
	'https://i.ytimg.com/' # 유튜브 썸네일
];

## 점검 기능 비활성화
$wgUseRCPatrol = false;
$wgUseNPPatrol = false;
$wgUseFilePatrol = false;

## 설정에서 옵션 숨기기
$wgHiddenPrefs = [
	'realname', # 실명 필요 없음
	'gender', # 성별 필요 없음
	'nickname', # 서명 사용자 지정 숨기기
	'fancysig',
];

# 외부 링크를 새 창에서 열기
$wgExternalLinkTarget = '_blank';

# 링크에 nofollow 붙이지 않기 
$wgNoFollowLinks = false;

$wgHooks['LinkerMakeExternalLink'][] = function( &$url, &$text, &$link, &$attribs, $linkType ) {
    global $wgServer;

    # $wgExternalLinkTarget = '_blank';에 따라 link rel에 noreferrer가 추가되지만, 추가되지 않기를 원하므로 제거
    if ( isset( $attribs['rel'] ) ) {
        $relValues = array_filter(
            explode( ' ', $attribs['rel'] ),
            fn( $v ) => $v !== 'noreferrer'
        );
        $rel = implode( ' ', $relValues );
        $attribs['rel'] = trim( $rel ) !== '' ? $rel : null;
        if ( $attribs['rel'] === null ) unset( $attribs['rel'] );
    }

    # 외부 링크 문법을 사용하지만 실제로는 내부 링크인 링크는 원래대로 현재 탭에서 열리도록
    if ( strpos( $url, $wgServer ) !== false ) {
        unset( $attribs['target'] );
    }

    return true;
};

# 표시 제목 허용
$wgRestrictDisplayTitle = false;

# 임시 계정 관련 설정
$wgAutoCreateTempUser['enabled'] = true;
$wgAutoCreateTempUser['genPattern'] = 'ㅇㅇ$1';
$wgAutoCreateTempUser['reservedPattern'] = 'ㅇㅇ$1';
$wgAutoCreateTempUser['serialProvider']['useYear'] = false;


## ---------- 확장 관련 설정 ----------

# -- Cargo --
# Cargo 확장용 별도 데이터베이스 설정
$wgCargoDBserver = "database";
$wgCargoDBname = "changpopwiki_cargo";
$wgCargoDBuser = "cargouser";
$wgCargoDBpassword = getenv('CARGOUSER_PASSWORD');

# 문자열에 '--' 가 있는 경우 Cargo 쿼리가 불가능한 문제를 회피하기 위해 REPLACE 함수를 허용
# 자세한 내용은 위키 내의 '틀:카고 쿼리 이스케이프' 참고
$wgCargoAllowedSQLFunctions[] = 'REPLACE';

# -- DisplayTitle --
# 부제목으로 실제 문서명 표기 숨기기
$wgDisplayTitleHideSubtitle = true;
# 리다이렉트 이름으로 링크한 페이지 실제 문서명 변환 사용하지 않음
$wgDisplayTitleFollowRedirects = false;

# -- EmbedVideo --
$wgEmbedVideoRequireConsent = false;

# -- ExternalData --

define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY'));

$wgExternalDataSources['시트'] = [
	'url' => 'https://sheets.googleapis.com/v4/spreadsheets/1vMnt6hYO662kuluJkDTNkWUCKLEGmS6v3IY4932tKwM/values/$시트명$?key=' . GOOGLE_API_KEY,
	'params' => ['시트명'],
	'format' => 'json',
	'use jsonpath' => true,
	'hidden' => true,
	'min cache seconds' => 5
];

$wgExternalDataSources['유튜브'] = [
	'url' => 'https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails,statistics&id=$id$&key=' . GOOGLE_API_KEY,
	'params' => ['id'],
	'format' => 'json',
	'use jsonpath' => true,
	'hidden' => true,
	'min cache seconds' => 3600
];

$wgExternalDataSources['유튜브채널'] = [
	'url' => 'https://www.googleapis.com/youtube/v3/channels?part=snippet,statistics&id=$id$&key=' . GOOGLE_API_KEY,
	'params' => ['id'],
	'format' => 'json',
	'use jsonpath' => true,
	'hidden' => true,
	'min cache seconds' => 86400
];

$wgExternalDataSources['틀데이터'] = [
	'url' => $wgInternalServer . $wgScriptPath . '/api.php?action=templatedata&lang=ko&format=json&titles=$틀이름$',
	'params' => ['틀이름'],
	'format' => 'json',
	'use jsonpath' => true,
	'hidden' => true,
	'min cache seconds' => 86400
];

$wgExternalDataSources['*']['min cache seconds'] = 0;

# -- PageForms --
# 클래스 생성 제한
$wgGroupPermissions['user']['createclass'] = false;

# -- ConfirmEdit --
$wgCaptchaClass = 'QuestyCaptcha';
$wgCaptchaQuestions = [
        "창팝과 관련된 아무 숫자나 입력해주세요" => explode(',', getenv('CAPTCHA_ANSWERS')),
];

# -- CodeMirror --

# 기본적으로 활성화
$wgDefaultUserOptions['usecodemirror'] = true;

# -- VisualEditor --

# 비주얼 에디터 활성화 이름공간 추가
$wgVisualEditorAvailableNamespaces = [
	NS_PROJECT => true,
	NS_HELP => true,
	NS_EXAMPLE => true,
	NS_DOC => true,
];

# -- TemplateStyles --
# TemplateStyles 확장용 기본 이름공간을 따로 만든 이름공간으로 설정
$wgTemplateStylesDefaultNamespace = NS_TEMPLATESTYLE;

## --- 그 외 훅을 이용한 설정 ---

# 시스템 메시지 변경
# 참고: 미디어위키 시스템 메시지의 키 이름은 본래 소문자로 시작하지만 
# 해당 훅의 작동 방식 상 문서 이름을 사용하므로 문서 이름처럼 대문자로 시작해야만 적용되는 것으로 보입니다.
$wgHooks['MessagesPreLoad'][] = static function ($title, &$message, $code) {
    static $messages = [
        
        'Privacy' => '',
        'Disclaimers' => '',
        
        'Copyrightwarning'  => '',
        'Copyrightwarning2'  => '',
    ];

    if ( isset( $messages[$title] ) ) { 
        $message = $messages[$title];
    }
};

# 특수:CargoQuery 페이지 전용 CSS 적용
$wgResourceModules['local.cargoquery.styles'] = [
	'class' => 'MediaWiki\ResourceLoader\WikiModule',
    'styles' => ['MediaWiki:Cargoquery.css'],
];
$wgHooks['BeforePageDisplay'][] = function ( OutputPage &$out, Skin &$skin ) {
    $title = $out->getTitle();
    if ( $title && $title->isSpecialPage() ) {
        if ( $title->getDBkey() === 'CargoQuery' ) {
            $out->addModuleStyles( 'local.cargoquery.styles' );
        }
    }
    return true;
};

# 통계용 추적 스크립트 삽입
$wgHooks['BeforePageDisplay'][] = function( OutputPage $out, Skin $skin ) {
    $out->addHeadItem( 'tracking_script', '
    <!-- Cloudflare Web Analytics -->
    <script defer src=\'https://static.cloudflareinsights.com/beacon.min.js\' data-cf-beacon=\'{"token": "0ff6e8c00e3d4514889772cd59e7c2ce"}\'></script>
    <!-- End Cloudflare Web Analytics -->
    <script defer src="https://cloud.umami.is/script.js" data-website-id="ef773a45-9fe1-4100-8a96-874d9bca2c74"></script>
    ');
};

## --- Turnstile 관련 ---
define('TURNSTILE_SITE_KEY', getenv('TURNSTILE_SITE_KEY'));
define('TURNSTILE_SECRET_KEY', getenv('TURNSTILE_SECRET_KEY'));

# Turnstile 검증이 필요한지 확인하고, 필요하면 검증 폼을 출력하는 함수
function requireTurnstileIfNeeded( OutputPage $out, WebRequest $request, User $user ): bool {
    # 로그인한 사용자는 Turnstile 검증을 건너뜀
    if ( $user->isRegistered() ) {
        return true;
    }

    $session = $request->getSession();
    $lastPassed = $session->get('turnstile_passed');
    
    # 30분 유효
    if ( $lastPassed && ( time() - $lastPassed < 1800 ) ) {
        return true;
    }
    
    # Turnstile 검증
    if ( $request->wasPosted() && $request->getVal('cf-turnstile-response') ) {
        $response = $request->getVal('cf-turnstile-response');

        $verify = file_get_contents( "https://challenges.cloudflare.com/turnstile/v0/siteverify", false, stream_context_create( [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query( [
                    'secret' => TURNSTILE_SECRET_KEY,
                    'response' => $response,
                    'remoteip' => $request->getIP(),
                ] )
            ]
        ] ) );

        $result = json_decode( $verify, true );
        if ( !empty( $result['success'] ) ) {
            $session->set( 'turnstile_passed', time() );
            $session->persist();
            
            return true;
        }
    }

    # 기존 요청 데이터를 hidden 필드로 생성
    $hiddenInputs = '';
    foreach ( $request->getValues() as $name => $value ) {
        if ( $name !== 'cf-turnstile-response' && is_scalar( $value ) ) {
            $hiddenInputs .= '<input type="hidden" name="' . htmlspecialchars( $name ) . '" value="' . htmlspecialchars( $value ) . '">';
        }
    }

    # Turnstile 폼 출력
    $out->clearHTML();
    $out->disable();
    
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<form id="turnstile-form" method="POST">';
    echo $hiddenInputs; # 기존 데이터 추가
    echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    echo '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars(TURNSTILE_SITE_KEY) . '" data-callback="onTurnstileSuccess" style="display: grid; height: 100%; align-items: center; justify-items: center; zoom: 1.5;" ></div>';
    echo '<noscript><input type="submit" value="계속"></noscript>';
    echo '</form>';

    echo <<<EOT
<script>
function onTurnstileSuccess(token) {
    document.getElementById('turnstile-form').submit();
}
</script>
EOT;
    
    return false;
}

# Action 및 URL 파라미터에 대한 Turnstile 검증
$wgHooks['MediaWikiPerformAction'][] = function ( IContextSource $context, &$action ) {
    $request = $context->getRequest();
    $user = $context->getUser();
    $output = $context->getOutput();

    $currentAction = $request->getVal( 'action', 'view' );
    
    # 1. 편집 창 진입(edit) 및 저장(submit) 액션 보호
    $protectedActions = [ 'edit', 'submit' ];
    if ( in_array( $currentAction, $protectedActions ) ) {
        return requireTurnstileIfNeeded( $output, $request, $user );
    }

    # (선택) 시각편집기(VisualEditor)를 사용하는 경우 veaction 파라미터 방어
    if ( $request->getVal( 'veaction' ) === 'edit' ) {
        return requireTurnstileIfNeeded( $output, $request, $user );
    }

    # 2. URL 파라미터 기반 보호 (이전 버전, 차이 보기)
    $hasDiff = $request->getVal( 'diff' ) !== null;
    $hasOldid = $request->getVal( 'oldid' ) !== null;

    if ( $hasDiff || $hasOldid ) {
        return requireTurnstileIfNeeded( $output, $request, $user );
    }

    return true;
};

# 특수 페이지에 대한 Turnstile 검증
$wgHooks['SpecialPageBeforeExecute'][] = function ( SpecialPage $special, $subPage ) {
    # 보호할 특수 페이지 목록
    $protectedSpecialPages = [
        'Whatlinkshere',		# 여기를 가리키는 문서
        #'Recentchanges',		# 최근 바뀜
		'Recentchangeslinked',	# 가리키는 글의 최근 바뀜
        'Log',					# 기록
		'Listusers',			# 사용자 목록
		'CargoQuery',			# 카고 쿼리
		'Drilldown',            # 카고 드릴다운
    ];

    $specialName = $special->getName();

    if ( in_array( $specialName, $protectedSpecialPages ) ) {
        $context = $special->getContext();
        $request = $context->getRequest();
        $user = $context->getUser();
        $output = $context->getOutput();
        
        return requireTurnstileIfNeeded( $output, $request, $user );
    }

    return true;
};

# 디버그용 설정 파일이 존재하는 경우 불러오기
$debugSettings = '/config/DebugSettings.php';
if ( file_exists( $debugSettings ) ) { require $debugSettings; }