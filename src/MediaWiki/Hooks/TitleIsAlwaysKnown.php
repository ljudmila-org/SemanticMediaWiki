<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DIProperty;
use SMW\MediaWiki\HookListener;
use Title;

/**
 * Allows overriding default behaviour for determining if a page exists
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class TitleIsAlwaysKnown implements HookListener {

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var mixed
	 */
	private $result;

	/**
	 * @since  2.0
	 *
	 * @param Title $title
	 * @param mixed &$result
	 */
	public function __construct( Title $title, &$result ) {
		$this->title = $title;
		$this->result =& $result;
	}

	/**
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function process() {
		// Two possible ways of going forward:
		//
		// The FIRST seen here is to use the hook to override the known status
		// for predefined properties in order to avoid any edit link
		// which makes no-sense for predefined properties
		//
		// The SECOND approach is to inject SMWWikiPageValue with a setLinkOptions setter
		// that enables to set the custom options 'known' for each invoked linker during
		// getShortHTMLText
		// $linker->link( $this->getTitle(), $caption, $customAttributes, $customQuery, $customOptions )
		//
		// @see also HooksTest::testOnTitleIsAlwaysKnown

		if ( $this->title->getNamespace() === SMW_NS_PROPERTY ) {
			if ( !DIProperty::newFromUserLabel( $this->title->getText() )->isUserDefined() ) {
				$this->result = true;
			}
		}

		return true;
	}

}
