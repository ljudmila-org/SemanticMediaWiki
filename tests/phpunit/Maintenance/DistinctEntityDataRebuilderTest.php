<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\DistinctEntityDataRebuilder;
use SMW\Options;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\Maintenance\DistinctEntityDataRebuilder
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DistinctEntityDataRebuilderTest extends \PHPUnit\Framework\TestCase {

	protected $obLevel;
	private $connectionManager;
	private $testEnvironment;

	// The Store writes to the output buffer during drop/setupStore, to avoid
	// inappropriate buffer settings which can cause interference during unit
	// testing, we clean the output buffer
	protected function setUp(): void {
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->setOption( 'smwgAutoRefreshSubject', true );

		$this->testEnvironment = new TestEnvironment();
		$spyLogger = $this->testEnvironment->newSpyLogger();

		$store->setLogger( $spyLogger );

		$this->testEnvironment->registerObject( 'Store', $store );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$this->connectionManager = $this->getMockBuilder( '\SMW\Connection\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->obLevel = ob_get_level();
		ob_start();

		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->testEnvironment->tearDown();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Maintenance\DistinctEntityDataRebuilder',
			new DistinctEntityDataRebuilder( $store, $titleFactory )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildSelectedPagesWithQueryOption() {
		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( Title::newFromText( __METHOD__ ) );

		$queryResult = $this->getMockBuilder( '\SMW\Query\QueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [ $subject ] );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->at( 0 ) )
			->method( 'getQueryResult' )
			->willReturn( 1 );

		$store->expects( $this->at( 1 ) )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'query' => '[[Category:Foo]]'
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithCategoryNamespaceFilter() {
		$row = new \stdClass;
		$row->cat_title = 'Foo';

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->stringContains( 'category' ),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything() )
			->willReturn( [ $row ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'categories' => true
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithPropertyNamespaceFilter() {
		$row = new \stdClass;
		$row->page_namespace = SMW_NS_PROPERTY;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->anything(),
				$this->anything(),
				[ 'page_namespace' => SMW_NS_PROPERTY ],
				$this->anything(),
				$this->anything() )
			->willReturn( [ $row ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'p' => true
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithPageOption() {
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleFactory = $this->getMockBuilder( '\SMW\MediaWiki\TitleFactory' )
			->disableOriginalConstructor()
			->getMock();

		$titleFactory->expects( $this->at( 0 ) )
			->method( 'newFromText' )
			->with( 'Main page' )
			->willReturn( Title::newFromText( 'Main page' ) );

		$titleFactory->expects( $this->at( 1 ) )
			->method( 'newFromText' )
			->with( 'Some other page' )
			->willReturn( Title::newFromText( 'Some other page' ) );

		$titleFactory->expects( $this->at( 2 ) )
			->method( 'newFromText' )
			->with( 'Help:Main page' )
			->willReturn( Title::newFromText( 'Main page', NS_HELP ) );

		$titleFactory->expects( $this->at( 3 ) )
			->method( 'newFromText' )
			->with( 'Main page' )
			->willReturn( Title::newFromText( 'Main page' ) );

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'page'  => 'Main page|Some other page|Help:Main page|Main page'
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);

		$this->assertEquals(
			3,
			$instance->getRebuildCount()
		);
	}

	/**
	 * @see Store::refreshData
	 */
	public function refreshDataOnMockCallback( &$index ) {
		$index++;
	}

}
