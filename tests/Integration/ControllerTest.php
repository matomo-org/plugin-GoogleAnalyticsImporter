<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\tests\Integration;

use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Admin\V1alpha\AnalyticsAdminServiceClient;
use Matomo\Dependencies\GoogleAnalyticsImporter\Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Piwik\Nonce;
use Piwik\Plugins\GoogleAnalyticsImporter\Controller;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\AuthorizationGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImporterGA4;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group GoogleAnalyticsImporter
 * @group GoogleAnalyticsImporter_Integration
 */
class ControllerTest extends IntegrationTestCase
{
    private $originalGet;

    /**
     * @var ImportStatus|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importStatus;

    public function setUp(): void
    {
        parent::setUp();
        $this->originalGet = $_GET;
        FakeAccess::clearAccess(true);
    }

    public function tearDown(): void
    {
        $_GET = $this->originalGet;
        FakeAccess::clearAccess();
        parent::tearDown();
    }

    public function test_startImportGA4_recordsTheOriginalErrorWhenTheImportFailsToStart()
    {
        $_GET = [
            'nonce' => Nonce::getNonce('GoogleAnalyticsImporter.startImportNonce'),
            'propertyId' => 'properties/12345',
        ];

        $this->importStatus
            ->expects(self::once())
            ->method('erroredImport')
            ->with(self::anything(), 'Unable to import site entity.');

        ob_start();
        try {
            (new Controller())->startImportGA4();
        } finally {
            $output = ob_get_clean();
        }

        self::assertStringNotContainsString('"result":"ok"', (string) $output);
    }

    public function provideContainerConfig()
    {
        $this->importStatus = $this->createMock(ImportStatus::class);

        $importer = $this->createMock(ImporterGA4::class);
        $importer->method('makeSite')->willReturn(null);

        $authorization = $this->createMock(AuthorizationGA4::class);
        $authorization->method('getClient')->willReturn($this->createMock(BetaAnalyticsDataClient::class));
        $authorization->method('getAdminClient')->willReturn($this->createMock(AnalyticsAdminServiceClient::class));

        return [
            'Piwik\Access' => new FakeAccess(),
            ImportStatus::class => $this->importStatus,
            ImporterGA4::class => $importer,
            AuthorizationGA4::class => $authorization,
        ];
    }
}
