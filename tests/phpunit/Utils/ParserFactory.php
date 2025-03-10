<?php

namespace SMW\Tests\Utils;

use ParserOptions;
use SMW\DIWikiPage;
use SMW\Tests\Utils\Mock\MockSuperUser;
use Title;
use User;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 */
class ParserFactory {

	public static function create( $title, ?User $user = null ) {
		if ( is_string( $title ) ) {
			$title = Title::newFromText( $title );
		}

		if ( $title instanceof DIWikiPage ) {
			$title = $title->getTitle();
		}

		return self::newFromTitle( $title, $user );
	}

	public static function newFromTitle( Title $title, ?User $user = null ) {
		if ( $user === null ) {
			$user = new MockSuperUser();
		}

		$parser = \MediaWiki\MediaWikiServices::getInstance()->getParserFactory()->create();
		$parser->setTitle( $title );
		$parser->setUser( $user );
		$parser->setOptions( new ParserOptions( $user ) );
		$parser->clearState();

		return $parser;
	}

}
