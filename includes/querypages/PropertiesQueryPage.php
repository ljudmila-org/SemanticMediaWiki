<?php

namespace SMW;

use Html;
use Skin;
use SMW\DataValues\TypesValue;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\Exception\PropertyNotFoundException;
use SMW\SQLStore\Lookup\ListLookup;
use SMWDIError;
use Title;

/**
 * Query class that provides content for the Special:Properties page
 *
 * @ingroup QueryPage
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class PropertiesQueryPage extends QueryPage {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( Store $store, Settings $settings ) {
		$this->store = $store;
		$this->settings = $settings;
	}

	/**
	 * @codeCoverageIgnore
	 * Returns available cache information (takes into account user preferences)
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getCacheInfo() {
		if ( $this->listLookup->isFromCache() ) {
			return $this->msg( 'smw-sp-properties-cache-info', $this->getLanguage()->userTimeAndDate( $this->listLookup->getTimestamp(), $this->getUser() ) )->parse();
		}

		return '';
	}

	/**
	 * @return string
	 */
	function getPageHeader() {
		return Html::rawElement(
			'p',
			[ 'class' => 'smw-sp-properties-docu' ],
			$this->msg( 'smw-sp-properties-docu' )->parse()
		) . $this->getSearchForm( $this->getRequest()->getVal( 'property', '' ), $this->getCacheInfo() ) .
		Html::element(
			'h2',
			[],
			$this->msg( 'smw-sp-properties-header-label' )->text()
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getName() {
		return 'Properties';
	}

	/**
	 * Format a result in the list of results as a string. We expect the
	 * result to be an array with one object of type DIProperty
	 * (normally) or maybe SMWDIError (if something went wrong), followed
	 * by a number (how often the property is used).
	 *
	 * @param Skin $skin provided by MediaWiki, not needed here
	 * @param mixed $result
	 * @return string
	 * @throws PropertyNotFoundException if the result was not of a supported type
	 */
	function formatResult( $skin, $result ) {
		[ $dataItem, $useCount ] = $result;

		if ( $dataItem instanceof DIProperty ) {
			return $this->formatPropertyItem( $dataItem, $useCount );
		} elseif ( $dataItem instanceof SMWDIError ) {
			return $this->getMessageFormatter()->clear()
				->setType( 'warning' )
				->addFromArray( [ $dataItem->getErrors(), 'ID: ' . ( isset( $dataItem->id ) ? $dataItem->id : 'N/A' ) ] )
				->getHtml();
		}

		throw new PropertyNotFoundException( 'PropertiesQueryPage expects results that are properties or errors.' );
	}

	/**
	 * Produce a formatted string representation for showing a property and
	 * its usage count in the list of used properties.
	 *
	 * @since 1.8
	 *
	 * @param DIProperty $property
	 * @param int $useCount
	 * @return string
	 */
	protected function formatPropertyItem( DIProperty $property, $useCount ) {
		// Clear formatter before invoking messages
		$this->getMessageFormatter()->clear();

		$diWikiPage = $property->getDiWikiPage();
		$title = $diWikiPage !== null ? $diWikiPage->getTitle() : null;

		if ( $useCount == 0 && !$this->settings->get( 'smwgPropertyZeroCountDisplay' ) ) {
			return '';
		}

		if ( $property->isUserDefined() ) {

			if ( $title === null ) {
				// Show even messed up property names.
				$typestring = '';
				$proplink = $property->getLabel();
				$this->getMessageFormatter()
					->addFromArray( [ 'ID: ' . ( isset( $property->id ) ? $property->id : 'N/A' ) ] )
					->addFromKey( 'smw_notitle', $proplink );
			} else {
				[ $typestring, $proplink ] = $this->getUserDefinedPropertyInfo( $title, $property, $useCount );
			}

			$infoLink = '';

			// Add a link to SearchByProperty to hopefully identify the
			// "hidden" reference
			if ( $useCount < 1 ) {
				$infoLink = '&#160;' . \SMWInfolink::newPropertySearchLink( '+', $property->getLabel(), '' )->getHTML( $this->getLinker() );
			}

			$proplink .= $infoLink;

		} else {
			[ $typestring, $proplink ] = $this->getPredefinedPropertyInfo( $property );
		}

		if ( $typestring === '' ) { // Built-ins have no type

			// @todo Should use numParams for $useCount?
			return $this->msg( 'smw_property_template_notype' )
				->rawParams( $proplink )->numParams( $useCount )->text() . ' ' .
				$this->getMessageFormatter()
					->setType( 'warning' )
					->escape( false )->getHtml();

		} else {

			// @todo Should use numParams for $useCount?
			return $this->msg( 'smw_property_template' )
				->rawParams( $proplink, $typestring )->numParams( $useCount )->escaped() . ' ' .
				$this->getMessageFormatter()
					->setType( 'warning' )
					->escape( false )->getHtml();

		}
	}

	/**
	 * Returns information related to user-defined properties
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param DIProperty $property
	 * @param int $useCount
	 *
	 * @return array
	 */
	private function getUserDefinedPropertyInfo( $title, $property, $useCount ) {
		if ( $useCount <= $this->settings->get( 'smwgPropertyLowUsageThreshold' ) ) {
			$this->getMessageFormatter()->addFromKey( 'smw_propertyhardlyused' );
		}

		// User defined types default to Page
		$typestring = TypesValue::newFromTypeId( $this->settings->get( 'smwgPDefaultType' ) )->getLongHTMLText( $this->getLinker() );

		$label = htmlspecialchars( $property->getLabel() );
		$linkAttributes = [];

		if ( isset( $property->id ) ) {
			$linkAttributes['title'] = 'ID: ' . $property->id;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $property );
		$dataValue->setLinkAttributes( $linkAttributes );

		$proplink = $dataValue->getFormattedLabel(
			DataValueFormatter::HTML_SHORT,
			$this->getLinker()
		);

		if ( !$title->exists() ) {
			$this->getMessageFormatter()->addFromKey( 'smw_propertylackspage' );
		}

		$typeProperty = new DIProperty( '_TYPE' );
		$types = $this->store->getPropertyValues( $property->getDiWikiPage(), $typeProperty );

		if ( is_array( $types ) && count( $types ) >= 1 ) {
			$typeDataValue = DataValueFactory::getInstance()->newDataValueByItem( current( $types ), $typeProperty );
			$typestring = $typeDataValue->getLongHTMLText( $this->getLinker() );
		} else {
			$this->getMessageFormatter()->addFromKey( 'smw_propertylackstype', $typestring );
		}

		return [ $typestring, $proplink ];
	}

	/**
	 * Returns information related to predefined properties
	 *
	 * @since 1.9
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	private function getPredefinedPropertyInfo( DIProperty $property ) {
		$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $property, null );

		$dataValue->setLinkAttributes( [
			'title' => 'ID: ' . ( isset( $property->id ) ? $property->id : 'N/A' )
					. ' (' . $property->getKey() . ')'
		] );

		$label = $dataValue->getFormattedLabel(
			DataValueFormatter::HTML_SHORT,
			$this->getLinker()
		);

		return [
			TypesValue::newFromTypeId( $property->findPropertyValueType() )->getLongHTMLText( $this->getLinker() ),
			$label
		];
	}

	/**
	 * Get the list of results.
	 *
	 * @param RequestOptions $requestOptions
	 * @return array of array( \SMW\DIProperty|SMWDIError, integer )
	 */
	function getResults( $requestOptions ) {
		$this->listLookup = $this->store->getPropertiesSpecial( $requestOptions );
		return $this->listLookup->fetchList();
	}

	/**
	 * @return array|null
	 */
	public function getQueryInfo(): ?array {
		return null;
	}

}
