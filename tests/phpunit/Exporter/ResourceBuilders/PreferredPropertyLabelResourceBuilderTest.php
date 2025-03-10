<?php

namespace SMW\Tests\Exporter\ResourceBuilders;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\ResourceBuilders\PreferredPropertyLabelResourceBuilder;
use SMW\Tests\TestEnvironment;
use SMWExpData as ExpData;

/**
 * @covers \SMW\Exporter\ResourceBuilders\PreferredPropertyLabelResourceBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PreferredPropertyLabelResourceBuilderTest extends \PHPUnit\Framework\TestCase {

	private $dataItemFactory;
	private $dataValueFactory;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->resetPoolCacheById( \SMWExporter::POOLCACHE_ID );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceof(
			PreferredPropertyLabelResourceBuilder::class,
			new PreferredPropertyLabelResourceBuilder()
		);
	}

	public function testIsNotResourceBuilderForNonPreferredPropertyLabelProperty() {
		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$instance = new PreferredPropertyLabelResourceBuilder();

		$this->assertFalse(
			$instance->isResourceBuilderFor( $property )
		);
	}

	public function testAddResourceValueForValidProperty() {
		$property = $this->dataItemFactory->newDIProperty( '_PPLB' );

		$monolingualTextValue = $this->dataValueFactory->newDataValueByProperty(
			$property,
			'Bar@en'
		);

		$expData = new ExpData(
			new ExpNsResource( 'Foobar', 'Bar', 'Mo', null )
		);

		$instance = new PreferredPropertyLabelResourceBuilder();

		$instance->addResourceValue(
			$expData,
			$property,
			$monolingualTextValue->getDataItem()
		);

		$this->assertTrue(
			$instance->isResourceBuilderFor( $property )
		);
	}

}
