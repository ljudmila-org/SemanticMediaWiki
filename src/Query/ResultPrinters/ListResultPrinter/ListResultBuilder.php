<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

use Linker;
use SMW\Localizer\Message;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\PrefixParameterProcessor;

/**
 * Class ListResultBuilder
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class ListResultBuilder {

	private static $defaultConfigurations = [
		'*' => [
			'value-open-tag' => '<span class="smw-value">',
			'value-close-tag' => '</span>',
			'field-open-tag' => '<span class="smw-field">',
			'field-close-tag' => '</span>',
			'field-label-open-tag' => '<span class="smw-field-label">',
			'field-label-close-tag' => '</span>',
			'field-label-separator' => ': ',
			'other-fields-open' => ' (',
			'other-fields-close' => ')',
		],
		'list' => [
			'row-open-tag' => '<span class="smw-row">',
			'row-close-tag' => '</span>',
			'result-open-tag' => '<span class="smw-format list-format $CLASS$">',
			'result-close-tag' => '</span>',
		],
		'ol' => [
			'row-open-tag' => "<li class=\"smw-row\">",
			'row-close-tag' => '</li>',
			'result-open-tag' => '<ol class="smw-format ol-format $CLASS$" start="$START$">',
			'result-close-tag' => '</ol>',
		],
		'ul' => [
			'row-open-tag' => '<li class="smw-row">',
			'row-close-tag' => '</li>',
			'result-open-tag' => '<ul class="smw-format ul-format $CLASS$">',
			'result-close-tag' => '</ul>',
		],
		'plainlist' => [
			'value-open-tag' => '',
			'value-close-tag' => '',
			'field-open-tag' => '',
			'field-close-tag' => '',
			'field-label-open-tag' => '',
			'field-label-close-tag' => '',
			'row-open-tag' => '',
			'row-close-tag' => '',
			'result-open-tag' => '',
			'result-close-tag' => '',
		],
	];

	/** @var Linker|null */
	private $linker = null;

	/** @var QueryResult */
	private $queryResult;

	/** @var ParameterDictionary */
	private $configuration;
	private $templateRendererFactory;
	private $listPlainByDefault;

	public function __construct( QueryResult $queryResult, Linker $linker, ?bool $listPlainByDefault = null ) {
		$this->linker = $linker;
		$this->queryResult = $queryResult;
		$this->configuration = new ParameterDictionary();
		$this->listPlainByDefault = $listPlainByDefault ?? $GLOBALS['smwgPlainList'];
	}

	/**
	 * @return string
	 */
	public function getResultText() {
		$this->prepareBuilt();

		return $this->getTemplateCall( 'introtemplate' ) .
			$this->get( 'result-open-tag' ) .

			implode( $this->get( 'sep' ), $this->getRowTexts() ) .

			$this->get( 'result-close-tag' ) .
			$this->getTemplateCall( 'outrotemplate' );
	}

	private function prepareBuilt() {
		$format = $this->getEffectiveFormat();

		$this->configuration->setDefault(
			array_merge(
				self::$defaultConfigurations['*'],
				self::$defaultConfigurations[$format],
				$this->getDefaultsFromI18N() )
		);

		if ( $this->get( 'template' ) !== '' ) {

			$this->set( [ 'value-open-tag' => '', 'value-close-tag' => '' ] );

		}

		$this->set( 'result-open-tag', $this->replaceVariables( $this->get( 'result-open-tag' ) ) );
	}

	/**
	 * @return string
	 */
	private function getEffectiveFormat() {
		$format = $this->get( 'format' );

		if ( in_array( $format, [ 'ol', 'ul', 'plainlist' ] ) ) {
			return $format;
		}

		if ( $this->get( 'template' ) !== '' ) {
			return 'plainlist';
		}

		return $this->listPlainByDefault ? 'plainlist' : 'list';
	}

	/**
	 * @param string $setting
	 * @param string $default
	 *
	 * @return mixed
	 */
	protected function get( $setting, $default = '' ) {
		return $this->configuration->get( $setting, $default );
	}

	/**
	 * @param string|string[] $setting
	 * @param string|null $value
	 */
	public function set( $setting, $value = null ) {
		$this->configuration->set( $setting, $value );
	}

	/**
	 * @return string[]
	 */
	private function getDefaultsFromI18N() {
		return [
			'field-label-separator' => Message::get( 'smw-format-list-field-label-separator' ),
			'other-fields-open' => Message::get( 'smw-format-list-other-fields-open' ),
			'other-fields-close' => Message::get( 'smw-format-list-other-fields-close' ),
		];
	}

	/**
	 * @param string $subject
	 *
	 * @return string
	 */
	private function replaceVariables( $subject ) {
		return str_replace( [ '$START$', '$CLASS$' ], [ htmlspecialchars( $this->get( 'offset' ) + 1 ), htmlspecialchars( $this->get( 'class' ) ) ], $subject );
	}

	/**
	 * @param string $param
	 *
	 * @return string
	 */
	private function getTemplateCall( $param ) {
		$templatename = $this->get( $param );

		if ( $templatename === '' ) {
			return '';
		}

		$templateRenderer = $this->getTemplateRendererFactory()->getTemplateRenderer();
		$templateRenderer->packFieldsForTemplate( $templatename );

		return $templateRenderer->render();
	}

	/**
	 * @return TemplateRendererFactory
	 */
	private function getTemplateRendererFactory() {
		if ( $this->templateRendererFactory === null ) {
			$this->templateRendererFactory = new TemplateRendererFactory( $this->getQueryResult() );
			$this->templateRendererFactory->setUserparam( $this->get( 'userparam' ) );
		}

		return $this->templateRendererFactory;
	}

	/**
	 * @return QueryResult
	 */
	private function getQueryResult() {
		return $this->queryResult;
	}

	/**
	 * @return string[]
	 */
	private function getRowTexts() {
		$queryResult = $this->getQueryResult();
		$queryResult->reset();

		$rowTexts = [];
		$num = $queryResult->getQuery()->getOffset();
		$rowBuilder = $this->getRowBuilder();

		while ( ( $row = $queryResult->getNext() ) !== false ) {

			$rowTexts[] =
				$this->get( 'row-open-tag' ) .
				$rowBuilder->getRowText( $row, $num ) .
				$this->get( 'row-close-tag' );

			$num++;
		}

		return $rowTexts;
	}

	/**
	 * @return RowBuilder
	 */
	private function getRowBuilder() {
		if ( $this->get( 'template' ) === '' ) {
			$rowBuilder = new SimpleRowBuilder();
			$rowBuilder->setLinker( $this->linker );
		} else {
			$rowBuilder = new TemplateRowBuilder( $this->getTemplateRendererFactory() );
		}

		$prefixParameterProcessor = new PrefixParameterProcessor( $this->queryResult->getQuery(),
			$this->configuration->get( 'prefix' ) );

		$valueTextsBuilder = new ValueTextsBuilder( $prefixParameterProcessor );
		$valueTextsBuilder->setLinker( $this->linker );
		$valueTextsBuilder->setConfiguration( $this->configuration );

		$rowBuilder->setValueTextsBuilder( $valueTextsBuilder );
		$rowBuilder->setConfiguration( $this->configuration );

		return $rowBuilder;
	}

}
