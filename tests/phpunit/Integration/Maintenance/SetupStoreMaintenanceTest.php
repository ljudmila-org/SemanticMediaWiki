<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\PHPUnitCompat;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class SetupStoreMaintenanceTest extends SMWIntegrationTestCase {

	use PHPUnitCompat;

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->runnerFactory  = $this->testEnvironment->getUtilityFactory()->newRunnerFactory();
		$this->titleValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newTitleValidator();
		$this->spyMessageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/../../Fixtures/Maintenance/test-import-19.7.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown(): void {
		$this->testEnvironment->flushPages( $this->importedTitles );
		parent::tearDown();
	}

	public function testSetupStore_Delete() {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( '\SMW\Maintenance\setupStore' );

		$maintenanceRunner->setQuiet();

		$maintenanceRunner->setOptions(
			[
				'delete' => true,
				'nochecks' => true
			]
		);

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->run();

		$this->assertContains(
			'Database table cleanup',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testSetupStore() {
		$this->importedTitles = [
			'Category:Lorem ipsum',
			'Lorem ipsum',
			'Elit Aliquam urna interdum',
			'Platea enim hendrerit',
			'Property:Has Url',
			'Property:Has annotation uri',
			'Property:Has boolean',
			'Property:Has date',
			'Property:Has email',
			'Property:Has number',
			'Property:Has page',
			'Property:Has quantity',
			'Property:Has temperature',
			'Property:Has text'
		];

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner( '\SMW\Maintenance\setupStore' );

		$maintenanceRunner->setMessageReporter(
			$this->spyMessageReporter
		);

		$maintenanceRunner->setQuiet();
		$maintenanceRunner->run();

		$this->assertContains(
			'Core table(s)',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
