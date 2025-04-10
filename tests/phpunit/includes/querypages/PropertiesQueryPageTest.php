<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\PropertiesQueryPage;
use SMW\Settings;

/**
 * @covers \SMW\PropertiesQueryPage
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PropertiesQueryPageTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $skin;
	private $settings;
	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->skin = $this->getMockBuilder( '\Skin' )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = Settings::newFromArray( [
			'smwgPDefaultType'              => '_wpg',
			'smwgPropertyLowUsageThreshold' => 5,
			'smwgPropertyZeroCountDisplay'  => true
		] );

		$this->dataItemFactory = new DataItemFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\PropertiesQueryPage',
			new PropertiesQueryPage( $this->store, $this->settings )
		);
	}

	public function testFormatResultDIError() {
		$error = $this->dataItemFactory->newDIError( 'Foo' );

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $error, null ]
		);

		$this->assertIsString(

			$result
		);

		$this->assertContains(
			'Foo',
			$result
		);
	}

	public function testInvalidResultThrowsException() {
		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$this->expectException( '\SMW\Exception\PropertyNotFoundException' );
		$instance->formatResult( $this->skin, null );
	}

	public function testFormatPropertyItemOnUserDefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, 42 ]
		);

		$this->assertContains(
			'Foo',
			$result
		);
	}

	public function testFormatPropertyItemOnPredefinedProperty() {
		$property = $this->dataItemFactory->newDIProperty( '_MDAT' );

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, 42 ]
		);

		$this->assertContains(
			'42',
			$result
		);
	}

	public function testFormatPropertyItemZeroDisplay() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->settings->set(
			'smwgPropertyZeroCountDisplay',
			false
		);

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, 0 ]
		);

		$this->assertEmpty(
			$result
		);
	}

	public function testFormatPropertyItemLowUsageThreshold() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$count  = 42;

		$this->settings->set(
			'smwgPropertyLowUsageThreshold',
			$count + 1
		);

		$this->settings->set(
			'smwgPDefaultType',
			'_wpg'
		);

		$instance = new PropertiesQueryPage(
			$this->store,
			$this->settings
		);

		$result = $instance->formatResult(
			$this->skin,
			[ $property, $count ]
		);

		$this->assertContains(
			'42',
			$result
		);
	}

}
