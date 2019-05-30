<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\GoogleAnalyticsImporter\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\DimensionMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\Importer;
use Piwik\SettingsPiwik;
use Piwik\Site;
use Piwik\Timer;
use Piwik\Url;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require_once PIWIK_INCLUDE_PATH . '/plugins/GoogleAnalyticsImporter/vendor/autoload.php';

// TODO: make sure same version of google api client is used in this & SearchEngineKeywordsPerformance
// (may have to add test in target plugin)
// TODO: support importing multiple views at once?
// TODO: support importing segments
class ImportReports extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('googleanalyticsimporter:import-reports');
        $this->setDescription('Import reports from one or more google analytics properties into Matomo sites.');
        $this->addOption('property', null, InputOption::VALUE_REQUIRED, 'The GA properties to import.');
        $this->addOption('account', null, InputOption::VALUE_REQUIRED, 'The account ID to get views from.');
        $this->addOption('view', null, InputOption::VALUE_REQUIRED, 'The View ID to use. If not supplied, the default View for the property is used.');
        $this->addOption('access-token', null, InputOption::VALUE_REQUIRED, 'The oauth access token to use.');
        $this->addOption('dates', null, InputOption::VALUE_REQUIRED, 'The dates to import.');
        $this->addOption('idsite', null, InputOption::VALUE_REQUIRED, 'The site to import into.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $accessToken = $input->getOption('access-token');

        /** @var \Google_Client $googleClient */
        $googleClient = StaticContainer::get('GoogleAnalyticsImporter.googleClient');
        $googleClient->setAccessToken($accessToken);

        $service = new \Google_Service_Analytics($googleClient);

        $viewId = $this->getViewId($input, $output, $service);
        $dates = $this->getDatesToImport($input);

        /** @var Importer $importer */
        $importer = StaticContainer::get(Importer::class);

        $idSite = $this->getIdSite($input);
        if (empty($idSite)
            && !empty($property)
            && !empty($account)
        ) {
            $idSite = $importer->makeSite($account, $property, $viewId);
        }

        $output->writeln("Importing reports for date range {$dates[0]} - {$dates[1]} from GA view $viewId.");

        $timer = new Timer();

        $importer->import($idSite, $viewId, $dates[0], $dates[1]);

        $output->writeln("Done in $timer.");
    }

    private function getViewId(InputInterface $input, OutputInterface $output, \Google_Service_Analytics $service)
    {
        $viewId = $input->getOption('view');
        if (!empty($viewId)) {
            return $viewId;
        }

        $propertyId = $input->getOption('property');
        $accountId = $input->getOption('account');
        if (empty($propertyId)
            && empty($accountId)
        ) {
            throw new \Exception("Either a single --view or both --property and --account must be supplied.");
        }

        // TODO: detect authorization issues & print required ID
        $profiles = $service->management_profiles->listManagementProfiles($accountId, $propertyId);

        /** @var \Google_Service_Analytics_Profile $profile */
        $profile = reset($profiles->getItems());
        $profileId = $profile->id;

        $output->writeln("No view ID supplied, using first profile in the supplied account/property: " . $profileId);

        return $profileId;
    }

    private function getDatesToImport(InputInterface $input)
    {
        $dates = $input->getOption('dates');
        $dates = explode(',', $dates);

        if (count($dates) != 2) {
            $this->invalidDatesOption();
        }

        return [
            $this->parseDate($dates[0]),
            $this->parseDate($dates[1]),
        ];
    }

    private function invalidDatesOption()
    {
        throw new \Exception("Invalid value for the dates option supplied, must be a comma separated value with two "
            . "dates, eg, 2014-02-03,2015-02-03");
    }

    private function parseDate($date)
    {
        try {
            return Date::factory($date);
        } catch (\Exception $ex) {
            return $this->invalidDatesOption();
        }
    }

    private function getIdSite(InputInterface $input)
    {
        $idSite = $input->getOption('idsite');
        if (!empty($idSite)) {
            if (!is_numeric($idSite)) {
                throw new \Exception("Invalid --idsite value provided, must be an integer.");
            }

            try {
                new Site($idSite);
            } catch (\Exception $ex) {
                throw new \Exception("Site ID $idSite does not exist.");
            }
        }
        return $idSite;
    }
}
