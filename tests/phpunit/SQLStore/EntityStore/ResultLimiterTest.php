<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\ResultLimiter;

/**
 * @covers \SMW\SQLStore\EntityStore\ResultLimiter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ResultLimiterTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ResultLimiter::class,
			new ResultLimiter()
		);
	}

	public function testCalcAndSkip() {
		$requestOptions = new RequestOptions();
		$requestOptions->exclude_limit = true;
		$requestOptions->setLimit( 2 );
		$requestOptions->setOffset( 1 );

		$instance = new ResultLimiter();
		$instance->calcSize( $requestOptions );

		$res = [];

		foreach ( [ '1', '2', '3', '4' ] as $value ) {

			if ( $instance->canSkip( 'foo' ) ) {
				continue;
			}

			$res[] = $value;
		}

		$this->assertEquals(
			[ '1', '2', '3' ],
			$res
		);
	}

	public function testNoSkip() {
		$requestOptions = new RequestOptions();

		$instance = new ResultLimiter();
		$instance->calcSize( $requestOptions );

		$res = [];

		foreach ( [ '1', '2', '3', '4' ] as $value ) {

			if ( $instance->canSkip( 'foo' ) ) {
				continue;
			}

			$res[] = $value;
		}

		$this->assertEquals(
			[ '1', '2', '3', '4' ],
			$res
		);
	}

}
