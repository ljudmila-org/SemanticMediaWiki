<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Utils\HtmlTabs;
use SMW\Utils\JsonView;
use WebRequest;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class MappingsInfoProvider extends InfoProviderHandler {

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSupplementTask() {
		return 'mappings';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {
		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-elastic-mappings-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-mappings-intro',
					$link
				]
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {
		$this->outputFormatter->setPageTitle( 'Elasticsearch mappings' );

		$this->outputFormatter->addParentLink(
			[ 'action' => $this->getParentTask() ],
			'smw-admin-supplementary-elastic-title'
		);

		$this->outputInfo();
	}

	private function outputInfo() {
		$connection = $this->getStore()->getConnection( 'elastic' );

		$mappings = [
			ElasticClient::TYPE_DATA => $connection->getMapping(
				[
					'index' => $connection->getIndexName( ElasticClient::TYPE_DATA )
				]
			),
			ElasticClient::TYPE_LOOKUP => $connection->getMapping(
				[
					'index' => $connection->getIndexName( ElasticClient::TYPE_LOOKUP )
				]
			)
		];

		$limits = [
			ElasticClient::TYPE_DATA => [
				'index.mapping.total_fields.limit' => $connection->getConfig()->dotGet( 'settings.data.index.mapping.total_fields.limit' )
			]
		];

		$this->outputFormatter->addHtml(
			Html::rawElement( 'p', [], $this->msg( 'smw-admin-supplementary-elastic-mappings-docu' ) )
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'es-mapping' );
		$htmlTabs->setActiveTab( 'summary' );

		$htmlTabs->tab(
			'summary',
			$this->msg( 'smw-admin-supplementary-elastic-mappings-summary' )
		);

		$htmlTabs->content(
			'summary',
			'<p style="margin-top:0.8em;">' . $this->msg( 'smw-admin-supplementary-elastic-mappings-docu-extra' ) . '</p>' .
			'<pre>' . $this->outputFormatter->encodeAsJson( $this->getSummary( $mappings ) ) . '</pre>' .
			'<h3 class="smw-title">' . $this->msg( 'smw-admin-supplementary-elastic-settings-title' ) . '</h3>' .
			'<pre>' . $this->outputFormatter->encodeAsJson( $limits ) . '</pre>'
		);

		$htmlTabs->tab(
			'fields',
			$this->msg( 'smw-admin-supplementary-elastic-mappings-fields' )
		);

		$jsonView = ( new JsonView() )->create(
			'elastic-mappings',
			$this->outputFormatter->encodeAsJson( $mappings ),
			2
		);

		$htmlTabs->content(
			'fields',
			$jsonView
		);

		$html = $htmlTabs->buildHTML( [ 'class' => 'es-mapping' ] );

		$this->outputFormatter->addHtml(
			$html
		);

		$this->outputFormatter->addInlineStyle(
			'.es-mapping #tab-summary:checked ~ #tab-content-summary,' .
			'.es-mapping #tab-fields:checked ~ #tab-content-fields {' .
			'display: block;}'
		);
	}

	private function getSummary( $mappings ) {
		$summary = [
			ElasticClient::TYPE_DATA => [
				'fields' => [
					'property_fields' => 0,
					'nested_fields' => 0
				],
				'total' => 0
			],
			ElasticClient::TYPE_LOOKUP => [
				'fields' => [
					'property_fields' => 0,
					'nested_fields' => 0
				],
				'total' => 0
			]
		];

		foreach ( $mappings as $type => $mapping ) {
			foreach ( $mapping as $inx ) {
				$this->countFields( $inx['mappings'], $type, $summary );
			}
		}

		return $summary;
	}

	private function countFields( $mapping, $type, &$count ) {
		foreach ( $mapping['properties'] as $k => $val ) {
			foreach ( $val as $p => $v ) {
				if ( $p === 'properties' ) {
					foreach ( $v as $field => $mappings ) {
						if ( is_string( $field ) ) {
							$count[$type]['fields']['property_fields']++;
						}

						if ( isset( $mappings['fields'] ) ) {
							$count[$type]['fields']['nested_fields'] += count( $mappings['fields'] );
						}
					}
				} elseif ( $p === 'type' ) {
					$count[$type]['fields']['property_fields']++;
				} elseif ( $p === 'fields' ) {
					$count[$type]['fields']['nested_fields'] += count( $v );
				}
			}
		}

		$count[$type]['total'] = $count[$type]['fields']['property_fields'] +
			$count[$type]['fields']['nested_fields'];
	}

}
