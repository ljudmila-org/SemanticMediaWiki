<?php

namespace SMW\DataValues;

use SMW\DataModel\ContainerSemanticData;
use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer\Localizer;
use SMW\SemanticData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;
use SMWDIContainer as DIContainer;

/**
 * MonolingualTextValue requires two components, a language code and a
 * text.
 *
 * A text `foo@en` is expected to be invoked with a BCP47 language
 * code tag and a language dependent text component.
 *
 * Internally, the value is stored as container object that represents
 * the language code and text as separate entities in order to be queried
 * individually.
 *
 * External output representation depends on the context (wiki, html)
 * whether the language code is omitted or not.
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 * @reviewer thomas-topway-it
 */
class MonolingualTextValue extends AbstractMultiValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '_mlt_rec';

	/**
	 * @var DIProperty[]|null
	 */
	private static $properties = null;

	/**
	 * nonstandardLanguageCodeMapping
	 */
	private $nonstandardLanguageCodeMapping;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
		$this->nonstandardLanguageCodeMapping = \LanguageCode::getNonstandardLanguageCodeMapping();
	}

	/**
	 * @see AbstractMultiValue::setFieldProperties
	 *
	 * @param DIProperty[] $properties
	 */
	public function setFieldProperties( array $properties ) {
		// Keep the interface while the properties for this type
		// are fixed.
	}

	/**
	 * @see AbstractMultiValue::getProperties
	 *
	 * @param DIProperty[] $properties
	 */
	public function getProperties() {
		self::$properties;
	}

	/**
	 * @since 2.5
	 *
	 * @param $text
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getTextWithLanguageTag( $text, $languageCode ) {
		$languageCode = Localizer::asBCP47FormattedLanguageCode( $languageCode );

		// @TODO test de-formal with PropertyListByApiRequest
		$mappedLanguageCode = array_search( $languageCode, $this->nonstandardLanguageCodeMapping ) ?: $languageCode;

		return $text . '@' . $mappedLanguageCode;
	}

	/**
	 * @see DataValue::parseUserValue
	 * @note called by DataValue::setUserValue
	 *
	 * @param string $userValue
	 */
	protected function parseUserValue( $userValue ) {
		[ $text, $languageCode ] = $this->getValuesFromString( $userValue );

		$languageCodeValue = $this->newLanguageCodeValue( $languageCode );

		if (
			( $languageCode !== '' && $languageCodeValue->getErrors() !== [] ) ||
			( $languageCode === '' && $this->isEnabledFeature( SMW_DV_MLTV_LCODE ) ) ) {
			$this->addError( $languageCodeValue->getErrors() );
			return;
		}

		$dataValues = [];

		foreach ( $this->getPropertyDataItems() as $property ) {

			if (
				( $languageCode === '' && $property->getKey() === '_LCODE' ) ||
				( $text === '' && $property->getKey() === '_TEXT' ) ) {
				continue;
			}

			$value = $text;

			if ( $property->getKey() === '_LCODE' ) {
				$value = $languageCode;
			}

			$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
				$property,
				$value,
				false,
				$this->m_contextPage
			);

			$this->addError( $dataValue->getErrors() );

			$dataValues[] = $dataValue;
		}

		// Generate a hash from the normalized representation so that foo@en being
		// the same as foo@EN independent of a user input
		$containerSemanticData = $this->newContainerSemanticData( $text . '@' . $languageCode );

		foreach ( $dataValues as $dataValue ) {
			$containerSemanticData->addDataValue( $dataValue );
		}

		// Remember the data to extend the sortkey
		$containerSemanticData->setExtensionData( 'sort.data', implode( ';', [ $text, $languageCode ] ) );

		$this->m_dataitem = new DIContainer( $containerSemanticData );
	}

	/**
	 * @note called by MonolingualTextValueDescriptionDeserializer::deserialize
	 * and MonolingualTextValue::parseUserValue
	 *
	 * No explicit check is made on the validity of a language code and is
	 * expected to be done before calling this method.
	 *
	 * @since 2.4
	 *
	 * @param string $userValue
	 *
	 * @return array
	 */
	public function getValuesFromString( $userValue ) {
		return $this->dataValueServiceFactory->getValueParser( $this )->parse( $userValue );
	}

	/**
	 * @see DataValue::loadDataItem
	 *
	 * @param DataItem $dataItem
	 *
	 * @return bool
	 */
	protected function loadDataItem( DataItem $dataItem ) {
		if ( $dataItem->getDIType() === DataItem::TYPE_CONTAINER ) {
			$this->m_dataitem = $dataItem;
			return true;
		} elseif ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE ) {

			$semanticData = null;
			$subobjectName = $dataItem->getSubobjectName();

			if ( $this->hasCallable( SemanticData::class ) ) {
				$semanticData = $this->getCallable( SemanticData::class )();
			}

			if (
				$semanticData instanceof SemanticData &&
				$semanticData->hasSubSemanticData( $subobjectName ) ) {
				$this->m_dataitem = new DIContainer(
					$semanticData->findSubSemanticData( $subobjectName )
				);
			} else {
				$monolingualTextLookup = ApplicationFactory::getInstance()->getStore()->service( 'MonolingualTextLookup' );
				$monolingualTextLookup->setCaller( __METHOD__ );
				$this->m_dataitem = $monolingualTextLookup->newDIContainer( $dataItem, $this->getProperty() );
			}

			return true;
		}

		return false;
	}

	/**
	 * @see DataValue::getShortWikiText
	 */
	public function getShortWikiText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::WIKI_SHORT, $linker );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::HTML_SHORT, $linker );
	}

	/**
	 * @see DataValue::getLongWikiText
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::WIKI_LONG, $linker );
	}

	/**
	 * @see DataValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::HTML_LONG, $linker );
	}

	/**
	 * @see DataValue::getWikiValue
	 */
	public function getWikiValue() {
		return $this->dataValueServiceFactory->getValueFormatter( $this )->format( DataValueFormatter::VALUE );
	}

	/**
	 * @since 2.4
	 * @note called by AbstractRecordValue::getPropertyDataItems
	 *
	 * @return DIProperty[]
	 */
	public function getPropertyDataItems() {
		if ( self::$properties !== null && self::$properties !== [] ) {
			return self::$properties;
		}

		foreach ( [ '_TEXT', '_LCODE' ] as $id ) {
			self::$properties[] = new DIProperty( $id );
		}

		return self::$properties;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function getDataItems() {
		return parent::getDataItems();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return DataValue|null
	 */
	public function getTextValueByLanguageCode( $languageCode ) {
		if ( ( $list = $this->toArray() ) === [] ) {
			return null;
		}

		$mappedLanguageCode = $this->nonstandardLanguageCodeMapping[$list['_LCODE']] ?? $list['_LCODE'];

		if ( $mappedLanguageCode !== Localizer::asBCP47FormattedLanguageCode( $languageCode ) ) {
			return null;
		}

		if ( $list['_TEXT'] === '' ) {
			return null;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			new DIBlob( $list['_TEXT'] ),
			new DIProperty( '_TEXT' )
		);

		return $dataValue;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function toArray() {
		if ( !$this->isValid() || $this->getDataItem() === [] ) {
			return [];
		}

		$semanticData = $this->getDataItem()->getSemanticData();

		$list = [
			'_TEXT' => '',
			'_LCODE' => ''
		];

		$dataItems = $semanticData->getPropertyValues( new DIProperty( '_TEXT' ) );
		$dataItem = reset( $dataItems );

		if ( $dataItem !== false ) {
			$list['_TEXT'] = $dataItem->getString();
		}

		$dataItems = $semanticData->getPropertyValues( new DIProperty( '_LCODE' ) );
		$dataItem = reset( $dataItems );

		if ( $dataItem !== false ) {
			$list['_LCODE'] = $dataItem->getString();
		}

		return $list;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function toString() {
		if ( !$this->isValid() || $this->getDataItem() === [] ) {
			return '';
		}

		$list = $this->toArray();

		return $list['_TEXT'] . '@' . $list['_LCODE'];
	}

	private function newContainerSemanticData( $value ) {
		if ( $this->m_contextPage === null ) {
			$containerSemanticData = ContainerSemanticData::makeAnonymousContainer();
			$containerSemanticData->skipAnonymousCheck();
		} else {
			$subobjectName = SMW_SUBENTITY_MONOLINGUAL . md5( $value );

			$subject = new DIWikiPage(
				$this->m_contextPage->getDBkey(),
				$this->m_contextPage->getNamespace(),
				$this->m_contextPage->getInterwiki(),
				$subobjectName
			);

			$containerSemanticData = new ContainerSemanticData( $subject );
		}

		return $containerSemanticData;
	}

	private function newLanguageCodeValue( $languageCode ) {
		$languageCodeValue = new LanguageCodeValue();

		if ( $this->m_property !== null ) {
			$languageCodeValue->setProperty( $this->m_property );
		}

		$languageCodeValue->setUserValue( $languageCode );

		return $languageCodeValue;
	}

}
