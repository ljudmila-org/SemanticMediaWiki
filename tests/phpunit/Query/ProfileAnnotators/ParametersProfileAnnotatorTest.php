<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use SMW\DataModel\ContainerSemanticData;
use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Query\ProfileAnnotators\ParametersProfileAnnotator;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\ParametersProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParametersProfileAnnotatorTest extends \PHPUnit\Framework\TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\ParametersProfileAnnotator',
			new ParametersProfileAnnotator( $profileAnnotator, $query )
		);
	}

	public function testCreateProfile() {
		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '', 'foo' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getLimit' )
			->willReturn( 42 );

		$query->expects( $this->once() )
			->method( 'getOffset' )
			->willReturn( 0 );

		$query->expects( $this->once() )
			->method( 'getQueryMode' )
			->willReturn( 1 );

		$instance = new ParametersProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$query
		);

		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 1,
			'propertyKeys'   => [ '_ASKPA' ],
			'propertyValues' => [ '{"limit":42,"offset":0,"sort":[],"order":[],"mode":1}' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}
