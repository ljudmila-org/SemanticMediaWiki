<?php

namespace SMW\Listener\EventListener\EventListeners;

use Onoi\EventDispatcher\DispatchContext;
use Onoi\EventDispatcher\EventListener;
use Psr\Log\LoggerAwareTrait;
use SMW\DIWikiPage;
use SMW\Query\Cache\ResultCache;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class InvalidateResultCacheEventListener implements EventListener {

	use LoggerAwareTrait;

	const EVENT_ID = 'InvalidateResultCache';

	/**
	 * @var ResultCache
	 */
	private $resultCache;

	/**
	 * @since 3.1
	 */
	public function __construct( ResultCache $resultCache ) {
		$this->resultCache = $resultCache;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function execute( ?DispatchContext $dispatchContext = null ) {
		if ( $dispatchContext === null ) {
			return;
		}

		if ( $dispatchContext->has( 'title' ) ) {
			$subject = DIWikiPage::newFromTitle( $dispatchContext->get( 'title' ) );
		} else {
			$subject = $dispatchContext->get( 'subject' );
		}

		if ( $dispatchContext->has( 'context' ) ) {
			$context = $dispatchContext->get( 'context' );
		} else {
			$context = 'n/a';
		}

		if ( $dispatchContext->has( 'dependency_list' ) ) {
			$items = $dispatchContext->get( 'dependency_list' );
		} else {
			$items = [ $subject ];
		}

		$this->resultCache->invalidateCache(
			$items,
			$context
		);

		$this->logger->info(
			[ 'Event', 'InvalidateResultCache', "{caused_by}", "{subject}" ],
			[ 'role' => 'user', 'caused_by' => $context, 'subject' => $subject ]
		);
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function isPropagationStopped() {
		return true;
	}

}
