<?php

namespace SMW\Tests;

use ReflectionClass;
use SMW\Query\ResultPrinters\ResultPrinter;

/**
 * Class contains methods to access data in connection with the QueryPrinter
 *
 * @since 1.9
 *
 * @file
 *
 * @license GPL-2.0-or-later
 * @author mwjames
 */

/**
 * Class contains methods to access data in connection with the QueryPrinter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
abstract class QueryPrinterTestCase extends \PHPUnit\Framework\TestCase {

	/**
	 * Helper method sets result printer parameters
	 *
	 * @param ResultPrinter $instance
	 * @param array $parameters
	 *
	 * @return ResultPrinter
	 */
	protected function setParameters( ResultPrinter $instance, array $parameters ) {
		$reflector = new ReflectionClass( $this->getClass() );
		$params = $reflector->getProperty( 'params' );
		$params->setAccessible( true );
		$params->setValue( $instance, $parameters );

		if ( isset( $parameters['searchlabel'] ) ) {
			$searchlabel = $reflector->getProperty( 'mSearchlabel' );
			$searchlabel->setAccessible( true );
			$searchlabel->setValue( $instance, $parameters['searchlabel'] );
		}

		if ( isset( $parameters['headers'] ) ) {
			$searchlabel = $reflector->getProperty( 'mShowHeaders' );
			$searchlabel->setAccessible( true );
			$searchlabel->setValue( $instance, $parameters['headers'] );
		}

		return $instance;
	}

	protected function arrayWrap( array $elements ) {
		return array_map(
			static function ( $element ) {
				return [ $element ];
			},
			$elements
		);
	}

}
