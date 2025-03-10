<?php

namespace SMW\MediaWiki;

use Job as MediaWikiJob;
use JobQueueGroup;
use Psr\Log\LoggerAwareTrait;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Site;
use SMW\Store;
use Title;

/**
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
abstract class Job extends MediaWikiJob {

	use LoggerAwareTrait;

	/**
	 * @var bool
	 */
	protected $isEnabledJobQueue = true;

	/**
	 * @var JobQueue
	 */
	protected $jobQueue;

	/**
	 * @var Job
	 */
	protected $jobs = [];

	/**
	 * @var Store
	 */
	protected $store = null;

	/**
	 * @since 2.1
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * Whether to insert jobs into the JobQueue is enabled or not
	 *
	 * @since 1.9
	 *
	 * @param bool|true $enableJobQueue
	 *
	 * @return AbstractJob
	 */
	public function isEnabledJobQueue( $enableJobQueue = true ) {
		$this->isEnabledJobQueue = (bool)$enableJobQueue;
		return $this;
	}

	/**
	 * @note Job::batchInsert was deprecated in MW 1.21
	 * JobQueueGroup::singleton()->push( $job ) was deprecated in MW 1.37;
	 * MediaWikiServices::getInstance()->getJobQueueGroup()
	 *
	 * @since 1.9
	 */
	public function pushToJobQueue() {
		$this->isEnabledJobQueue ? self::batchInsert( $this->jobs ) : null;
	}

	/**
	 * @note Job::getType was introduced with MW 1.21
	 *
	 * @return string
	 */
	public function getType() {
		return $this->command;
	}

	/**
	 * @since  2.0
	 *
	 * @return int
	 */
	public function getJobCount() {
		return count( $this->jobs );
	}

	/**
	 * @since  1.9
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function hasParameter( $key ) {
		if ( !is_array( $this->params ) ) {
			return false;
		}

		return isset( $this->params[$key] ) || array_key_exists( $key, $this->params );
	}

	/**
	 * @since  1.9
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function getParameter( $key, $default = false ) {
		return $this->hasParameter( $key ) ? $this->params[$key] : $default;
	}

	/**
	 * @since  3.0
	 *
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function setParameter( $key, $value ) {
		$this->params[$key] = $value;
	}

	/**
	 * @see https://gerrit.wikimedia.org/r/#/c/162009
	 *
	 * @param self[] $jobs
	 *
	 * @return bool
	 */
	public static function batchInsert( $jobs ) {
		return ApplicationFactory::getInstance()->getJobQueue()->push( $jobs );
	}

	/**
	 * @see Job::insert
	 */
	public function insert() {
		if ( $this->isEnabledJobQueue ) {
			return self::batchInsert( [ $this ] );
		}
	}

	/**
	 * @see JobQueueGroup::lazyPush
	 *
	 * @note Registered jobs are pushed using JobQueueGroup::pushLazyJobs at the
	 * end of MediaWiki::restInPeace
	 *
	 * @since 3.0
	 */
	public function lazyPush() {
		if ( $this->isEnabledJobQueue ) {
			return $this->getJobQueue()->lazyPush( $this );
		}
	}

	/**
	 * @see Translate::TTMServerMessageUpdateJob
	 * @since 3.0
	 *
	 * @param int $delay
	 */
	public function setDelay( $delay ) {
		$isDelayedJobsEnabled = $this->getJobQueue()->isDelayedJobsEnabled(
			$this->getType()
		);

		if ( !$delay || !$isDelayedJobsEnabled ) {
			return;
		}

		$oldTime = $this->getReleaseTimestamp();
		$newTime = time() + $delay;

		if ( $oldTime !== null && $oldTime >= $newTime ) {
			return;
		}

		$this->params['jobReleaseTimestamp'] = $newTime;
	}

	/**
	 * @see Job::newRootJobParams
	 * @since 3.0
	 */
	public static function newRootJobParams( $key = '', $title = '' ) {
		if ( $title instanceof Title ) {
			$title = $title->getPrefixedDBkey();
		}

		return parent::newRootJobParams( "job:{$key}:root:{$title}" );
	}

	/**
	 * @see Job::ignoreDuplicates
	 * @since 3.0
	 */
	public function ignoreDuplicates() {
		if ( isset( $this->params['waitOnCommandLine'] ) ) {
			return $this->params['waitOnCommandLine'] > 1;
		}

		return $this->removeDuplicates;
	}

	/**
	 * Only run the job via commandLine or the cronJob and avoid execution via
	 * Special:RunJobs as it can cause the script to timeout.
	 */
	public function waitOnCommandLineMode() {
		if ( !$this->hasParameter( 'waitOnCommandLine' ) || Site::isCommandLineMode() ) {
			return false;
		}

		if ( $this->hasParameter( 'waitOnCommandLine' ) ) {
			$this->params['waitOnCommandLine'] = $this->getParameter( 'waitOnCommandLine' ) + 1;
		} else {
			$this->params['waitOnCommandLine'] = 1;
		}

		$job = new static( $this->title, $this->params );
		$job->insert();

		return true;
	}

	protected function getJobQueue() {
		if ( $this->jobQueue === null ) {
			$this->jobQueue = ApplicationFactory::getInstance()->getJobQueue();
		}

		return $this->jobQueue;
	}

}
