<?php

namespace SMW\Tests\SQLStore\Exception;

use SMW\SQLStore\Exception\TableMissingIdFieldException;

/**
 * @covers \SMW\SQLStore\Exception\TableMissingIdFieldException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TableMissingIdFieldExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new TableMissingIdFieldException( 'foo' );

		$this->assertInstanceof(
			TableMissingIdFieldException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
