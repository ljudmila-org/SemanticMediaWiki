<?php

namespace SMW\Tests\Maintenance;

use SMW\DIConcept;
use SMW\Maintenance\ConceptCacheRebuilder;

/**
 * @covers \SMW\Maintenance\ConceptCacheRebuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class ConceptCacheRebuilderTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$store = $this->getMockForAbstractClass( '\SMW\Store' );

		$settings = $this->getMockBuilder( '\SMW\Settings' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Maintenance\ConceptCacheRebuilder',
			new ConceptCacheRebuilder( $store, $settings )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildWithoutOptionsAndActions() {
		$store = $this->getMockForAbstractClass( '\SMW\Store' );

		$settings = $this->getMockBuilder( '\SMW\Settings' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptCacheRebuilder(
			$store,
			$settings
		);

		$this->assertFalse( $instance->rebuild() );
	}

	/**
	 * @dataProvider actionProvider
	 */
	public function testRebuildFullConceptWithoutRangeSelectionOnMockStore( $action ) {
		$concept = new DIConcept( 'Foo', '', '', '', '' );

		$concept->setCacheStatus( 'full' );
		$concept->setCacheDate( '1358515326' );
		$concept->setCacheCount( '1000' );

		$instance = $this->acquireInstanceFor( $concept );

		$instance->setParameters( [
			$action => true
		] );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @dataProvider actionProvider
	 */
	public function testRebuildEmptyConceptWithoutRangeSelectionOnMockStore( $action ) {
		$concept = new DIConcept( 'Foo', '', '', '', '' );
		$concept->setCacheStatus( 'empty' );

		$instance = $this->acquireInstanceFor( $concept );

		$instance->setParameters( [
			$action => true
		] );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @dataProvider actionProvider
	 */
	public function testRebuildFullConceptWithRangeSelectionOnMockStore( $action ) {
		$concept = new DIConcept( 'Foo', '', '', '', '' );

		$concept->setCacheStatus( 'full' );
		$concept->setCacheDate( '1358515326' );
		$concept->setCacheCount( '1000' );

		$instance = $this->acquireInstanceFor( $concept );

		$instance->setParameters( [
			$action => true,
			's'     => 0,
			'e'     => 90000
		] );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @dataProvider actionProvider
	 */
	public function testRebuildSingleEmptyConceptWithRangeSelectionOnMockStore( $action ) {
		$concept = new DIConcept( 'Foo', '', '', '', '' );
		$concept->setCacheStatus( 'empty' );

		$instance = $this->acquireInstanceFor( $concept );

		$instance->setParameters( [
			$action => true,
			's'     => 0,
			'e'     => 90000
		] );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @dataProvider actionProvider
	 */
	public function testRebuildSingleFullConceptOnMockStore( $action ) {
		$concept = new DIConcept( 'Foo', '', '', '', '' );

		$concept->setCacheStatus( 'full' );
		$concept->setCacheDate( '1358515326' );
		$concept->setCacheCount( '1000' );

		$instance = $this->acquireInstanceFor( $concept );

		$instance->setParameters( [
			$action   => true,
			'old'     => 10,
			'concept' => 'Bar'
		] );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @dataProvider actionProvider
	 */
	public function testRebuildWithNullConceptOnMockStore( $action ) {
		$instance = $this->acquireInstanceFor( null );

		$instance->setParameters( [
			$action   => true,
			'concept' => 'Bar'
		] );

		$this->assertTrue( $instance->rebuild() );
	}

	private function acquireInstanceFor( $concept = null ) {
		$expectedToRun = $concept !== null ? $this->any() : $this->never();
		$refreshConceptCacheReturn = $concept !== null ? $concept->getConceptQuery() : null;

		$row = new \stdClass;
		$row->page_namespace = 0;
		$row->page_title = 1;

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $expectedToRun )
			->method( 'select' )
			->willReturn( [ $row ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConceptCacheStatus' )
			->willReturn( $concept );

		$store->expects( $expectedToRun )
			->method( 'refreshConceptCache' )
			->willReturn( [ $refreshConceptCacheReturn ] );

		$store->expects( $expectedToRun )
			->method( 'getConnection' )
			->willReturn( $database );

		$settings = $this->getMockBuilder( '\SMW\Settings' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConceptCacheRebuilder(
			$store,
			$settings
		);

		$instance->setParameters( [
			'quiet' => true,
		] );

		return $instance;
	}

	public function actionProvider() {
		return [
			[ 'status' ],
			[ 'create' ],
			[ 'delete' ]
		];
	}

}
