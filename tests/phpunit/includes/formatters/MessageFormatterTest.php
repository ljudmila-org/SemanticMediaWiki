<?php

namespace SMW\Tests;

use Message;
use ReflectionClass;
use SMW\MessageFormatter;

/**
 * Tests for the MessageFormatter class
 *
 * @file
 *
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\MessageFormatter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class MessageFormatterTest extends SemanticMediaWikiTestCase {

	use PHPUnitCompat;

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\MessageFormatter';
	}

	/**
	 * Helper method that returns an MessageFormatter instance
	 *
	 * @since 1.9
	 *
	 * @return MessageFormatter
	 */
	private function getInstance() {
		return new MessageFormatter( $this->getLanguage() );
	}

	/**
	 * @test MessageFormatter::__construct
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test MessageFormatter::newFromArray
	 * @test MessageFormatter::setType
	 * @test MessageFormatter::getHtml
	 * @dataProvider getDataProvider
	 *
	 * @since  1.9
	 *
	 * @param array $messages
	 */
	public function testNewFromArray( array $messages ) {
		$instance = MessageFormatter::newFromArray(
			$this->getLanguage(),
			$messages
		);

		$instance->setType( 'error' );
		$this->assertIsString( $instance->getHtml() );

		$instance->setType( 'warning' );
		$this->assertIsString( $instance->getHtml() );

		$instance->setType( 'info' );
		$this->assertIsString( $instance->getHtml() );
	}

	/**
	 * @test MessageFormatter::addFromKey
	 * @test MessageFormatter::getMessages
	 *
	 * @since 1.9
	 */
	public function testAddFromKey() {
		$instance = $this->getInstance();
		$param = '1001';

		$instance->addFromKey( 'Foo', $param )
			->addFromKey( 'Bar', $param )
			->addFromKey( 'Foo', $param );

		$messages = $instance->getMessages();

		// Returns count of existing with duplicates, elimination is
		// applied only during output (getHtml/getPlain)
		$this->assertCount( 3, $messages );

		foreach ( $messages as $msg ) {
			$this->assertInstanceOf( '\Message', $msg );

			foreach ( $msg->getParams() as $result ) {
				$this->assertEquals( $param, $result );
			}
		}
	}

	/**
	 * @test MessageFormatter::setLanguage
	 * @test MessageFormatter::getPlain
	 *
	 * @since 1.9
	 */
	public function testSetLanguage() {
		$key = 'properties';
		$msg = new Message( $key );
		$instance = $this->getInstance();

		$instance->addFromKey( $key );
		$instance->setLanguage( $this->getLanguage( 'zh-tw' ) );

		$this->assertEquals(
			$msg->inLanguage( $this->getLanguage( 'zh-tw' ) )->text(),
			$instance->getPlain()
		);

		$instance->clear();
		$this->assertEmpty( $instance->getPlain() );
	}

	/**
	 * @test MessageFormatter::format
	 * @dataProvider getDataProvider
	 *
	 * @since  1.9
	 *
	 * @param array $messages
	 * @param int $count
	 */
	public function testFormat( array $messages, $count ) {
		$instance = $this->getInstance();
		$instance->addFromArray( $messages );

		// Access protected method
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'doFormat' );
		$method->setAccessible( true );

		// Test array normalization and deletion of duplicates
		$result = $method->invoke( $instance, $instance->getMessages() );
		$this->assertCount( $count, $result );
	}

	/**
	 * @test MessageFormatter::getHtml
	 * @dataProvider getDataProvider
	 *
	 * @since  1.9
	 *
	 * @param array $messages
	 */
	public function testGetHtml( array $messages ) {
		$instance = $this->getInstance();
		$instance->addFromArray( $messages );

		$this->assertIsString( $instance->getHtml() );
	}

	/**
	 * @test MessageFormatter::getPlain
	 * @dataProvider getDataProvider
	 *
	 * @since  1.9
	 *
	 * @param array $messages
	 */
	public function testGetPlain( array $messages ) {
		$instance = $this->getInstance();
		$instance->addFromArray( $messages );

		$this->assertIsString( $instance->getPlain() );
	}

	/**
	 * @test MessageFormatter::escape
	 * @test MessageFormatter::getPlain
	 *
	 * @since  1.9
	 */
	public function testEscapedUnescaped() {
		$instance = $this->getInstance();
		$instance->addFromArray( [ '<Foo>' ] );

		$this->assertEquals( '&lt;Foo&gt;', $instance->escape( true )->getPlain() );
		$this->assertEquals( '<Foo>', $instance->escape( false )->getPlain() );
	}

	/**
	 * Message from different sources could have different depth therefore
	 * objects need to be resolved recursively in order to ensure a 1-n array
	 *
	 */
	public function getDataProvider() {
		return [

			// #0 Empty array
			[ [], 0 ],

			// #1 Simple string elements 5 elements (one duplicate) = 4
			[
				[
					'Foo', 'Bar', [ 'FooBar', [ 'barFoo', 'Foo' ] ]
				],
				4
			],

			// #2 A duplicate Message object = 1
			[
				[
					new Message( 'smw_iq_disabled' ),
					new Message( 'smw_iq_disabled' )
				],
				1
			],

			// #3 Different Message objects
			[
				[
					new Message( 'smw_iq_disabled' ),
					new Message( 'smw_multiple_concepts' )
				],
				2
			],

			// #4 Invoked MessageFormatter object (recursive test)
			[
				[
					new Message( 'smw_iq_disabled' ),
					[ new Message( 'smw_iq_disabled' ),
							new Message( 'smw_multiple_concepts' )
					]
				],
				2
			],

			// #5 Combine different objects (recursive test) containing 7 messages
			// where two of them are duplicates resulting in 5 objects
			[
				[
					new Message( 'smw_iq_disabled' ),
					new Message( 'smw_multiple_concepts' ),
					[
						new Message( 'smw_iq_disabled' ),
						'Foo'
					],
					[
						[
							new Message( 'smw_no_concept_namespace' ),
							new Message( 'foo' ),
							'Foo'
						]
					]
				],
				5
			],
		];
	}
}
