<?php

$wgHooks['ParserFirstCallInit'][] = static function ( \Parser $parser ) {
    \MediaWiki\MediaWikiServices::getInstance()
            ->getContentLanguage()
            ->mMagicExtensions['가사문서목록'] = [ '가사문서목록', '가사문서목록' ];

    // 위키텍스트에서 {{#가사문서목록: <곡문서이름> }} 과 같이 사용 가능.
    // 해당 곡 문서의 모든 가사 변형 문서를 ';;' 구분자로 연결된 문자열로 출력합니다.
    $parser->setFunctionHook(
            '가사문서목록',
            static function ( \Parser $parser, $song = '' ) {
                $song = trim( $song );

                if ( $song === '' ) {
                    return '';
                }

                $request = new \MediaWiki\Request\FauxRequest( [
                        'action' => 'query',
                        'list' => 'allpages',
                        'apnamespace' => NS_LYRIC,
                        'apprefix' => $song . '/',
                        'aplimit' => 'max',
                ] );

                $api = new \ApiMain( $request );
                $api->execute();

                $pages = $api->getResult()->getResultData()['query']['allpages'] ?? [];

                return implode( ';;', array_column( $pages, 'title' ) );
            }
    );

    return true;
};