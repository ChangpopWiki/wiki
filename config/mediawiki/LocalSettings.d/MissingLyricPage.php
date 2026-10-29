<?php
// SpecialMissingLyricPages 클래스 정의
// 분류명과 이름공간 모두 고정 (LocalSettings.php에 정의된 NS_ 상수 사용)
// MW 1.44+ categorylinks 스키마: cl_to 대신 linktarget 테이블 통한 cl_target_id 사용

use MediaWiki\SpecialPage\QueryPage;
use MediaWiki\Title\Title;

class SpecialMissingLyricPages extends QueryPage  {

    // 대상 분류명 (공백 그대로, 내부에서 언더스코어로 변환)
    private const TARGET_CATEGORY = '창팝';

    public function __construct() {
        parent::__construct( 'MissingLyricPages' );
    }

    public function isExpensive() {
        // false: 매번 실시간 쿼리. true로 하면 updateSpecialPages.php 크론으로
        // querycache 테이블에 캐시된 결과를 보여주는 방식으로 바뀜(대규모 위키용).
        return false;
    }

    public function getQueryInfo() {
        return [
            'tables' => [
                'p1' => 'page',
                'cl'  => 'categorylinks',
                'lt'  => 'linktarget',
                'p2'  => 'page',
            ],
            'fields' => [
                'namespace' => 'p1.page_namespace',
                'title'     => 'p1.page_title',
                'value'     => 'p1.page_title', // 정렬 기준
            ],
            'conds' => [
                'p1.page_namespace' => NS_MAIN,
                'p2.page_id' => null,
            ],
            'join_conds' => [
                'cl' => [ 'JOIN', 'cl.cl_from = p1.page_id' ],
                'lt' => [ 'JOIN', [
                    'lt.lt_id = cl.cl_target_id',
                    'lt.lt_namespace' => NS_CATEGORY,
                    'lt.lt_title' => str_replace( ' ', '_', self::TARGET_CATEGORY ),
                ] ],
                'p2' => [ 'LEFT JOIN', [
                    'p2.page_namespace' => NS_LYRIC,
                    'p2.page_title = p1.page_title',
                ] ],
            ],
        ];
    }

    public function sortDescending() {
        return false;
    }

    public function formatResult( $skin, $row ) {
        $title = Title::makeTitle( $row->namespace, $row->title );
        return $this->getLinkRenderer()->makeLink( $title );
    }

    public function getDescription() {
        return new RawMessage( '가사 없는 곡 문서 목록' );
    }

    protected function getPageHeader() {
        return \MediaWiki\Html\Html::rawElement( 'div', [ 'class' => 'mw-specialpage-summary' ],
            '가사가 없는 곡 문서 목록입니다.' );
    }

    protected function getGroupName() {
        return 'maintenance';
    }
}

// 등록
$wgAutoloadClasses['SpecialMissingLyricPages'] = __FILE__;
$wgSpecialPages['MissingLyricPages'] = static function () {
    return new SpecialMissingLyricPages();
};