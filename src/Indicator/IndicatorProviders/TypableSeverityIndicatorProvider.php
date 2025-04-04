<?php

namespace SMW\Indicator\IndicatorProviders;

use SMW\Indicator\IndicatorProvider;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
interface TypableSeverityIndicatorProvider extends IndicatorProvider {

	/**
	 * No severity information available.
	 */
	const SEVERITY_NONE = '';

	/**
	 * Indicates that an indicator describes an informal state.
	 */
	const SEVERITY_INFO = 'severity/info';

	/**
	 * Indicates that an indicator describes a warning state.
	 */
	const SEVERITY_WARNING = 'severity/warning';

	/**
	 * Indicates that an indicator describes an error state.
	 */
	const SEVERITY_ERROR = 'severity/error';

	/**
	 * @since 3.2
	 *
	 * @param string $severityType
	 *
	 * @return bool
	 */
	public function isSeverityType( string $severityType ): bool;

}
