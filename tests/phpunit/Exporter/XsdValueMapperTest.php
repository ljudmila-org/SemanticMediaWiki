<?php

namespace SMW\Tests\Exporter;

use SMW\DataModel\ContainerSemanticData;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\XsdValueMapper;
use SMW\Tests\PHPUnitCompat;
use SMWDIBlob;
use SMWDIBoolean;
use SMWDINumber;
use SMWDITime;

/**
 * @covers \SMW\Exporter\XsdValueMapper
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class XsdValueMapperTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	/**
	 * @dataProvider supportedDataItemProvider
	 */
	public function testMatchSupportedTypes( $dataItem, $xsdValue, $xsdType ) {
		[ $type, $value ] = XsdValueMapper::map( $dataItem );

		$this->assertEquals(
			$xsdValue,
			$value
		);

		$this->assertContains(
			$xsdType,
			$type
		);
	}

	/**
	 * @dataProvider unsupportedDataItemProvider
	 */
	public function testTryToMatchUnsupportedTypeThrowsException( $dataItem ) {
		$this->expectException( 'RuntimeException' );
		XsdValueMapper::map( $dataItem );
	}

	public function supportedDataItemProvider() {
		# 0
		$provider[] = [
			new SMWDINumber( 42 ),
			'42',
			'double'
		];

		# 1
		$provider[] = [
			new SMWDIBlob( 'Test' ),
			'Test',
			'string'
		];

		# 2
		$provider[] = [
			new SMWDIBoolean( true ),
			'true',
			'boolean'
		];

		# 3
		$provider[] = [
			new SMWDITime( 1, '1970' ),
			'1970',
			'gYear'
		];

		# 4
		$provider[] = [
			new SMWDITime( 1, '1970', '12' ),
			'1970-12',
			'gYearMonth'
		];

		# 5
		$provider[] = [
			new SMWDITime( 1, '1970', '12', '31' ),
			'1970-12-31Z',
			'date'
		];

		# 6
		$provider[] = [
			new SMWDITime( 1, '1970', '12', '31', '12' ),
			'1970-12-31T12:00:00Z',
			'dateTime'
		];

		return $provider;
	}

	public function unsupportedDataItemProvider() {
		$dataItem = $this->getMockBuilder( '\SMWDataItem' )
			->disableOriginalConstructor()
			->setMethods( [ '__toString' ] )
			->getMockForAbstractClass();

		$dataItem->expects( $this->any() )
			->method( '__toString' )
			->willReturn( 'Foo' );

		# 0
		$provider[] = [
			$dataItem
		];

		# 1
		$provider[] = [
			new \SMWDIGeoCoord( [ 'lat' => 52, 'lon' => 1 ] )
		];

		# 2
		$provider[] = [
			new DIConcept( 'Foo', '', '', '', '' )
		];

		# 3
		$provider[] = [
			new \SMWDIUri( 'http', '//example.org', '', '' )
		];

		# 4
		$provider[] = [
			new \SMWDIContainer( new ContainerSemanticData( new DIWikiPage( 'Foo', NS_MAIN ) ) )
		];

		# 5
		$provider[] = [
			new DIWikiPage( 'Foo', NS_MAIN )
		];

		# 6
		$provider[] = [
			new DIProperty( 'Foo' )
		];

		# 7 Not a gregorian calendar model
		$provider[] = [
			new SMWDITime( 2, '1970' )
		];

		return $provider;
	}

}
