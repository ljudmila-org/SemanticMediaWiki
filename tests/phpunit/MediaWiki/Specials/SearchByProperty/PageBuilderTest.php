<?php

namespace SMW\Tests\MediaWiki\Specials\SearchByProperty;

use SMW\DIWikiPage;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\SearchByProperty\PageBuilder;
use SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions;
use SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\MediaWiki\Specials\SearchByProperty\PageBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class PageBuilderTest extends \PHPUnit\Framework\TestCase {

	private $stringValidator;
	private $localizer;

	protected function setUp(): void {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();
		$this->localizer = Localizer::getInstance();
	}

	public function testCanConstruct() {
		$HtmlFormRenderer = $this->getMockBuilder( '\SMW\MediaWiki\Renderer\HtmlFormRenderer' )
			->disableOriginalConstructor()
			->getMock();

		$PageRequestOptions = $this->getMockBuilder( '\SMW\MediaWiki\Specials\SearchByProperty\PageRequestOptions' )
			->disableOriginalConstructor()
			->getMock();

		$QueryResultLookup = $this->getMockBuilder( '\SMW\MediaWiki\Specials\SearchByProperty\QueryResultLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SearchByProperty\PageBuilder',
			new PageBuilder( $HtmlFormRenderer, $PageRequestOptions, $QueryResultLookup )
		);
	}

	public function testGetHtmlForExactValueSearch() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'numParams' )
			->willReturnSelf();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( $message );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [
				new DIWikiPage( 'ResultOne', NS_MAIN ),
				new DIWikiPage( 'ResultTwo', NS_HELP ) ] );

		$instance =	new PageBuilder(
			new HtmlFormRenderer( $title, $messageBuilder ),
			new PageRequestOptions( 'Foo/Bar', [] ),
			new QueryResultLookup( $store )
		);

		$expected = [
			'value="Foo"',
			'value="Bar"',
			'title="ResultOne',
			'title="' . $this->localizer->getNsText( NS_HELP ) . ':ResultTwo'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function testGetHtmlForNearbyResultsSearch() {
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'numParams' )
			->willReturnSelf();

		$message->expects( $this->any() )
			->method( 'rawParams' )
			->willReturnSelf();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->willReturn( $message );

		$queryResult = $this->getMockBuilder( '\SMW\Query\QueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->willReturn( false );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertySubjects' )
			->willReturn( [
				new DIWikiPage( 'ResultOne', NS_MAIN ),
				new DIWikiPage( 'ResultTwo', NS_HELP ) ] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$requestOptions = [
			'propertyString' => 'Foo',
			'valueString' => 'Bar',
			'nearbySearchForType' => [ '_wpg' ]
		];

		$instance =	new PageBuilder(
			new HtmlFormRenderer( $title, $messageBuilder ),
			new PageRequestOptions( 'Foo/Bar', $requestOptions ),
			new QueryResultLookup( $store )
		);

		$expected = [
			'value="Foo"',
			'value="Bar"',
			'title="ResultOne',
			'title="' . $this->localizer->getNsText( NS_HELP ) . ':ResultTwo'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

}
