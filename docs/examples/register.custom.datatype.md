## Register new datatype

This example shows how to register a new dataType/dataValue in Semantic MediaWiki and the convention for the datatype key is to use `___` as leading identifer to distinguish them from those defined by Semantic MediaWiki itself. A matching new Property may also need to be created, see SMW::Property::initProperties. TYPE_ID should be unique - it cannot match any other Datatype ID or a Property ID.

### SMW::DataType::initTypes

```php
use MediaWiki\MediaWikiServices;
use SMW\DataTypeRegistry;
use Foo\DataValues\FooValue;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::DataType::initTypes', function () {
	$dataTypeRegistry = DataTypeRegistry::getInstance();
	$dataTypeRegistry->registerDatatype(
		FooValue::TYPE_ID,
		FooValue::class,
		DataItem::TYPE_BLOB
		FooValue::LABEL, // default false
		$isSubDataType, // default false
		$isBrowseableType // default false
	);

	$dataTypeRegistry->setOption(
		'foovalue.SomeSetting',
		42
	);

	return true;
};
```

### DataValue representation

```php
class FooValue extends DataValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '___foo_bar';

	/**
	 * @see DataValue::parseUserValue
	 * @note called by DataValue::setUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $userValue ) {
		...
	}
}
```

### Usage

```php
$fooValue = DataValueFactory::getInstance()->newTypeIdValue(
	'___foo_bar',
	'Bar'
)

$fooValue->getShortWikiText();
```
