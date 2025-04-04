<?php

namespace SMW\Tests\Utils\JSONScript;

use FauxRequest;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use OutputPage;
use RequestContext;
use SMW\Tests\Utils\File\ContentsReader;
use SMW\Tests\Utils\Mock\MockSuperUser;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class SpecialPageTestCaseProcessor extends MediaWikiIntegrationTestCase {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	/**
	 * @var bool
	 */
	private $debug = false;

	/**
	 * @var string
	 */
	private $testCaseLocation = '';

	/**
	 * @param Store
	 * @param StringValidator
	 */
	public function __construct( $store, $stringValidator ) {
		$this->store = $store;
		$this->stringValidator = $stringValidator;
	}

	/**
	 * @since  2.4
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $testCaseLocation
	 */
	public function setTestCaseLocation( $testCaseLocation ) {
		$this->testCaseLocation = $testCaseLocation;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $case
	 */
	public function process( array $case ) {
		if ( !isset( $case['special-page'] ) ) {
			return;
		}

		if ( isset( $case['special-page']['query-parameters'] ) ) {
			$queryParameters = $case['special-page']['query-parameters'];
		} else {
			$queryParameters = [];
		}

		$text = $this->getTextForRequestBy(
			MediaWikiServices::getInstance()->getSpecialPageFactory()->getPage( $case['special-page']['page'] ),
			new FauxRequest( $case['special-page']['request-parameters'] ),
			$queryParameters
		);

		$this->assertOutputForCase( $case, $text );
	}

	private function getTextForRequestBy( $page, $request, $queryParameters ) {
		$response = $request->response();

		$page->setContext( $this->makeRequestContext(
			$request,
			new MockSuperUser,
			$page->getPageTitle()
		) );

		$out = $page->getOutput();

		ob_start();
		$page->execute( $queryParameters );

		if ( $out->getRedirect() !== '' ) {
			$out->output();
			$text = ob_get_contents();
		} elseif ( $out->isDisabled() ) {
			$text = ob_get_contents();
		} else {
			$text = $out->getHTML();
		}

		ob_end_clean();

		if ( $this->debug ) {
			var_dump(
				"\n\n== DEBUG (start) ==\n\n" . $text .
				"\n\n== DEBUG (end) ==\n\n"
			);
		}

		$code = $response->getStatusCode();

		if ( $code > 0 ) {
			$response->header( "Status: " . $code . ' ' . \HttpStatus::getMessage( $code ) );
		}

		return $text;
	}

	private function assertOutputForCase( $case, $text ) {
		// Avoid issue with \r carriage return and \n new line
		$text = str_replace( "\r\n", "\n", $text );

		if ( isset( $case['assert-output']['to-contain'] ) ) {

			if ( isset( $case['assert-output']['to-contain']['contents-file'] ) ) {
				$contents = ContentsReader::readContentsFrom(
					$this->testCaseLocation . $case['assert-output']['to-contain']['contents-file']
				);
			} else {
				$contents = $case['assert-output']['to-contain'];
			}

			$this->stringValidator->assertThatStringContains(
				$contents,
				$text,
				$case['about']
			);
		}

		if ( isset( $case['assert-output']['not-contain'] ) ) {

			if ( isset( $case['assert-output']['not-contain']['contents-file'] ) ) {
				$contents = ContentsReader::readContentsFrom(
					$this->testCaseLocation . $case['assert-output']['not-contain']['contents-file']
				);
			} else {
				$contents = $case['assert-output']['not-contain'];
			}

			$this->stringValidator->assertThatStringNotContains(
				$contents,
				$text,
				$case['about']
			);
		}
	}

	/**
	 * @return RequestContext
	 */
	private function makeRequestContext( \WebRequest $request, $user, $title ) {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();

		$context = new RequestContext();
		$context->setRequest( $request );

		$out = new OutputPage( $context );
		$out->setTitle( $title );

		$context->setOutput( $out );
		$context->setLanguage( $languageFactory->getLanguage( $GLOBALS['wgLanguageCode'] ) );

		$user = $user === null ? new MockSuperUser() : $user;
		$context->setUser( $user );

		return $context;
	}
}
