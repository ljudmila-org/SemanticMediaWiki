<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use Title;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class NullJob extends Job {

	/**
	 * @since 2.5
	 *
	 * @param Title|null $title
	 * @param array $params job parameters
	 */
	public function __construct( ?Title $title = null, $params = [] ) {
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run() {
		return true;
	}

	/**
	 * @see Job::insert
	 *
	 * @since  2.5
	 */
	public function insert() {
	}

}
