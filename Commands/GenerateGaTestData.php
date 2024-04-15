<?php

/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\GoogleAnalyticsImporter\Commands;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Development;
use Piwik\Http;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Log\LoggerInterface;
class GenerateGaTestData extends ConsoleCommand
{
    private $visitorIdSeeds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25];
    private $referrers = [
        // 3 facebook
        "https://www.facebook.com/Divezone.net",
        "https://www.facebook.com/Divezone.net",
        "https://www.facebook.com/Divezone.net",
        // 3 reddit
        "https://www.reddit.com/r/scuba/comments/8mpl4j/open_water_divers_being_born/",
        "https://www.reddit.com/r/scuba/comments/8mrd88/snapped_this_pic_on_my_first_dive_post_cert/",
        "https://www.reddit.com/r/scuba/comments/8mtp0i/five_rules_shearwater_broke_with_the_teric/",
        // twitter
        "https://twitter.com/Divezone",
        "https://twitter.com/Divezone",
        "https://twitter.com/Divezone",
        // 10 websites
        "http://scubadiverlife.com/gear-category/exposure-suit",
        "http://scubadiverlife.com/gear-category/fins-reviews-2",
        "http://scubadiverlife.com/gear-category/bcd",
        "http://www.emperordivers.com/blog/conservation",
        "http://www.emperordivers.com/blog/dive-courses",
        "http://www.divein.com/guide/going-pro-become-a-dive-master",
        "http://www.divein.com/guide/how-to-become-a-padi-dive-instructor",
        "http://divebums.com/dive-sites/index.html",
        "http://divebums.com/dive-sites/La-Jolla-Shores.html#Secret-Garden",
        "https://www.oahuscubadiving.com/oahu-dive-sites/",
        // 5 direct
        '',
        '',
        '',
        '',
        '',
        // 6 search engine
        'https://www.google.com/search?q=here+is+a+test+search',
        'https://www.google.com/search?q=some+search',
        'https://www.google.com/search?q=relevant+keywords',
        'https://www.google.com/search?q=irrelevant+keywords',
        'https://www.google.com/search?q=irreverant+search',
        'https://www.google.com/search?q=immaculate+search',
        // params added if this is rolled
        'CAMPAIGN',
        'CAMPAIGN',
        'CAMPAIGN',
        'CAMPAIGN',
        'CAMPAIGN',
        'CAMPAIGN',
    ];
    private $userTypes = ['rogue', 'warrior', 'priest', 'paladin', 'ranger'];
    private $happinessLevels = ['low', 'medium', 'high'];
    private $alignments = ['chaotic good', 'chaotic neutral', 'chaotic evil', 'neutral good', 'true neutral', 'neutral evil', 'lawful good', 'lawful neutral', 'lawful evil'];
    private $pages = [
        // goal pages
        ['path' => '/', 'title' => 'BLOG!!'],
        ['path' => '/blog/about/', 'title' => 'ABOUT!!'],
        ['path' => '/blog/inde-par-region-et-ville/', 'title' => 'inde par region et ville'],
        ['path' => '/', 'title' => 'BLOG!!'],
        ['path' => '/blog/about/', 'title' => 'ABOUT!!'],
        ['path' => '/blog/inde-par-region-et-ville/', 'title' => 'inde par region et ville'],
        ['path' => '/', 'title' => 'BLOG!!'],
        ['path' => '/blog/about/', 'title' => 'ABOUT!!'],
        ['path' => '/blog/inde-par-region-et-ville/', 'title' => 'inde par region et ville'],
        ['path' => '/', 'title' => 'BLOG!!'],
        ['path' => '/blog/about/', 'title' => 'ABOUT!!'],
        ['path' => '/blog/inde-par-region-et-ville/', 'title' => 'inde par region et ville'],
        ['path' => '/', 'title' => 'BLOG!!'],
        ['path' => '/blog/about/', 'title' => 'ABOUT!!'],
        ['path' => '/blog/inde-par-region-et-ville/', 'title' => 'inde par region et ville'],
        ['path' => '/', 'title' => 'BLOG!!'],
        ['path' => '/blog/about/', 'title' => 'ABOUT!!'],
        ['path' => '/blog/inde-par-region-et-ville/', 'title' => 'inde par region et ville'],
        // other pages
        ['path' => '/blog/tevinter/solas', 'title' => 'Solas'],
        ['path' => '/blog/free-marches/markhem', 'title' => 'Markhem'],
        ['path' => '/blog/antiva/seleny', 'title' => 'Seleny'],
        ['path' => '/blog/anderfels/laysh', 'title' => 'Laysh'],
        ['path' => '/blog/orlais/emprise-du-lion/sahrnia', 'title' => 'Sahrnia'],
        ['path' => '/blog/orlais/halamshiral/winter-palace', 'title' => 'Winter Palace'],
        ['path' => '/blog/nevarra/trevis', 'title' => 'Trevis'],
        ['path' => '/blog/ferelden/the-hinterlands/redcliffe', 'title' => 'Redcliffe'],
        ['path' => '/blog/ferelden/crestwood', 'title' => 'Crestwood'],
        ['path' => '/blog/ferelden/crestwood/caer-bronach', 'title' => 'Caer Bronach'],
        ['path' => '/blog/orlais/the-western-approach', 'title' => 'Western Approach'],
        ['path' => '/blog/orlais/the-arbor-wilds', 'title' => 'The Arbor Wilds'],
    ];
    private $eventCategories = ['mauve', 'scarlet', 'viridian', 'keylime'];
    private $eventActions = ['burn knuckle', 'falcon punch', 'uppercut', 'izuna drop', 'spear throw', 'dragon punch'];
    private $eventLabels = ['Cammy', 'Akuma', 'Chun-Li', 'Ryu', 'Vega', 'Ibuki', 'Crimson Viper'];
    private $products = [['name' => 'The Fowl Wind', 'sku' => 'DAI2784', 'category' => 'Bows', 'price' => 1874], ['name' => 'Misfortune\'s Bite', 'sku' => 'DAI6503', 'category' => 'Axes', 'price' => 2109], ['name' => 'Path to Glory', 'sku' => 'DAI0663', 'category' => 'Shields', 'price' => 1345], ['name' => 'The Last Word', 'sku' => 'DAI6406', 'category' => 'Bows', 'price' => 987], ['name' => 'Earnest Reprisal', 'sku' => 'DAI7456', 'category' => 'Staff', 'price' => 1567], ['name' => 'Helm of Drasca', 'sku' => 'DAI7474', 'category' => 'Helmet', 'price' => 783], ['name' => 'Curse of Morrac', 'sku' => 'DAI8832', 'category' => 'Helmet', 'price' => 1294], ['name' => 'Masterwork Prowler Armor', 'sku' => 'DAI4293', 'category' => 'Armor', 'price' => 348], ['name' => 'Mask of the Grand Duchess', 'sku' => 'DAI0285', 'category' => 'Helmet', 'price' => 2396], ['name' => 'Keen Wyvern Vitaar', 'sku' => 'DAI0921', 'category' => 'Helmet', 'price' => 10]];
    private $searchQueries = ['the wrath of heaven', 'in your heart shall burn', 'memories of the grey', 'promise of destruction', 'subjected to his will', 'one less venatori', 'the magister\'s birthright', 'demands of the qun', 'under her skin', 'measuring the veil', 'seeing red', 'high stakes', 'wyrm hole', 'rift at caer bronach', 'a fallen sister', 'chateau d\'onterre', 'the tiniest cave', 'the corruption of sahrnia', 'rifts in the springs', 'no word back', 'silence on the plains', 'sketch of the enavuris river', 'the spirit calmed', 'know thy enemy', 'agrarian apostate', 'letter from a lover', 'shallow breaths', 'where the drufallo roam'];
    private $searchCategories = ['inquisitor\'s path', 'specializations', 'inner circle', 'lost temple of dirthamen', 'skyhold', 'hissing wastes', 'haven', 'winter palace'];
    private $ipAddresses = [
        '194.57.91.215',
        // in Besançon, FR (unicode city name)
        '::ffff:137.82.130.49',
        // in British Columbia (mapped ipv4)
        '137.82.130.0',
        // anonymization tests
        '137.82.0.0',
        '2003:f6:93bf:26f:9ec7:a6ff:fe29:27df',
        // ipv6 in US (without region or city)
        '113.62.1.1',
        // in Lhasa, Tibet
        '151.100.101.92',
        // in Rome, Italy (using country DB, so only Italy will show)
        '103.29.196.229',
    ];
    private $userAgents = ['Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.136 Mobile Safari/537.36', 'Mozilla/5.0 (Linux; U; Android 2.3.7; fr-fr; HTC Desire Build/GRI40; MildWild CM-8.0 JG Stable) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1', 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36', 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; GTB6.3; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; OfficeLiveConnector.1.4; OfficeLivePatch.1.3)', 'Mozilla/5.0 (Windows NT 6.1; Trident/7.0; MDDSJS; rv:11.0) like Gecko', 'Mozilla/5.0 (Linux; Android 4.1.1; SGPT13 Build/TJDS0170) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Safari/537.36', 'Mozilla/5.0 (Linux; U; Android 4.3; zh-cn; SM-N9006 Build/JSS15J) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 MQQBrowser/5.0 Mobile Safari/537.36', 'Mozilla/5.0 (X11; U; Linux i686; ru; rv:1.9.0.14) Gecko/2009090216 Ubuntu/9.04 (jaunty) Firefox/3.0.14'];
    private $currentClientId;
    /**
     * @var LoggerInterface
     */
    private $logger;
    protected function configure()
    {
        $this->setName('googleanalyticsimporter:generate-ga-test-data');
        $this->setDescription('Tracks some fake test data to Google Analytics.');
        $this->addRequiredValueOption('account-id', null, 'The UA-... account ID.');
    }
    public function isEnabled()
    {
        return Development::isEnabled();
    }
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $this->logger = StaticContainer::get(LoggerInterface::class);
        $accountId = $input->getOption('account-id');
        if (empty($accountId)) {
            throw new \Exception('--account-id is required!');
        }
        while (\true) {
            $this->currentClientId = null;
            $output->writeln('Tracking visit...');
            $this->trackVisit($accountId);
            $secs = $this->getRandomVisitWait();
            $output->writeln("Waiting {$secs}s...");
            sleep($secs);
        }
    }
    private function trackVisit($accountId)
    {
        $this->sendPageview($accountId);
        sleep(1);
        if ($this->getChance() > 25) {
            $this->sendEvent($accountId);
        }
        sleep(1);
        if ($this->getChance() > 55) {
            $this->sendEcommerce($accountId);
        }
        sleep(1);
        if ($this->getChance() > 65) {
            $this->sendSiteSearch($accountId);
        }
    }
    private function sendSiteSearch($accountId)
    {
        $this->currentClientId = null;
        $params = $this->baseParams($accountId);
        $searchQuery = $this->getRandomElement($this->searchQueries);
        $searchCategory = $this->getRandomElement($this->searchCategories);
        $params['t'] = 'pageview';
        $params['dl'] = 'http://matthieu.net/search?s=' . urlencode($searchQuery) . '&search_category=' . urlencode($searchCategory);
        $params['dt'] = 'Search';
        $this->send($params);
        sleep(1);
        $this->sendPageview($accountId);
    }
    private $campaignNames = ['campaign 1', 'campaign 2', 'campaign 3', 'campaign 4', 'campaign 5'];
    private $campaignSources = ['campaign source 1', 'campaign source 2', 'campaign source 3', 'campaign source 4', 'campaign source 5'];
    private $campaignKeywords = ['campaign keyword 1', 'campaign keyword 2', 'campaign keyword 3', 'campaign keyword 4', 'campaign keyword 5'];
    private $campaignMediums = ['campaign medium 1', 'campaign medium 2', 'campaign medium 3', 'campaign medium 4', 'campaign medium 5'];
    private $campaignContents = ['campaign content 1', 'campaign content 2', 'campaign content 3', 'campaign content 4', 'campaign content 5'];
    private $campaignIds = ['campaign id 1', 'campaign id 2', 'campaign id 3', 'campaign id 4', 'campaign id 5'];
    private function sendPageview($accountId)
    {
        $page = $this->getRandomElement($this->pages);
        $newVisit = !$this->currentClientId;
        $params = $this->baseParams($accountId);
        if ($newVisit) {
            $referrer = $this->getRandomElement($this->referrers);
            if ($referrer == 'CAMPAIGN') {
                // cn, cs, cm, ck, cc, ci
                $params['cn'] = $this->getRandomElement($this->campaignNames);
                $params['cs'] = $this->getRandomElement($this->campaignSources);
                $params['cm'] = $this->getRandomElement($this->campaignMediums);
                $params['ck'] = $this->getRandomElement($this->campaignKeywords);
                $params['cc'] = $this->getRandomElement($this->campaignContents);
                $params['ci'] = $this->getRandomElement($this->campaignIds);
            } else {
                if ($referrer) {
                    $params['dr'] = $referrer;
                }
            }
            $params['sc'] = 'start';
        }
        $params['t'] = 'pageview';
        $params['dl'] = 'http://matthieu.net' . $page['path'];
        $params['dt'] = $page['title'];
        $params['cd4'] = $this->getRandomElement($this->userTypes);
        $params['cd5'] = $this->getRandomElement($this->happinessLevels);
        $params['cd6'] = $this->getRandomEventValue();
        $params['cd7'] = $this->getRandomElement($this->alignments);
        $params['pdt'] = $this->getRandomPageDownloadTime();
        $this->send($params);
    }
    private function sendEvent($accountId)
    {
        $params = $this->baseParams($accountId);
        $params['t'] = 'event';
        $params['ec'] = $this->getRandomElement($this->eventCategories);
        $params['ea'] = $this->getRandomElement($this->eventActions);
        $params['el'] = $this->getRandomElement($this->eventLabels);
        $params['ev'] = $this->getRandomEventValue();
        $this->send($params);
    }
    private function sendEcommerce($accountId)
    {
        $params = $this->baseParams($accountId);
        $params['t'] = 'transaction';
        $transactionId = time() . Common::getRandomString();
        $itemCount = $this->getRandomItemCount();
        $items = array_merge($this->products);
        shuffle($items);
        $quantities = [];
        $revenue = 0;
        for ($i = 0; $i != $itemCount; ++$i) {
            $quantity = $this->getRandomQuantity();
            $quantities[] = $quantity;
            $revenue += $items[$i]['price'] * $quantity;
        }
        $params['ti'] = $transactionId;
        $params['ta'] = 'Matthieu Store Online';
        $params['tr'] = $revenue;
        $params['tt'] = $revenue * 0.1;
        $params['ts'] = $this->getRandomShipping();
        $params['cu'] = 'EUR';
        $this->send($params);
        for ($j = 0; $j != $itemCount; ++$j) {
            $params = $this->baseParams($accountId);
            $params['t'] = 'item';
            $params['ti'] = $transactionId;
            $params['in'] = $items[$j]['name'];
            $params['ip'] = $items[$j]['price'];
            $params['iq'] = $quantities[$j];
            $params['ic'] = $items[$j]['sku'];
            $params['iv'] = $items[$j]['category'];
            $params['cu'] = 'EUR';
            $this->send($params);
        }
    }
    private function baseParams($accountId)
    {
        return ['v' => 1, 'tid' => $accountId, 'cid' => $this->makeClientId(), 'uip' => $this->getRandomElement($this->ipAddresses), 'ua' => $this->getRandomElement($this->userAgents)];
    }
    private function getRandomElement($values)
    {
        $key = array_rand($values);
        return $values[$key];
    }
    private function getRandomVisitWait()
    {
        return random_int(1, 8);
    }
    private function getChance()
    {
        return random_int(0, 100);
    }
    private function getRandomEventValue()
    {
        return random_int(0, 500);
    }
    private function getRandomShipping()
    {
        return round(rand() * 30, 2);
    }
    private function getRandomItemCount()
    {
        return random_int(1, 5);
    }
    private function getRandomQuantity()
    {
        return random_int(1, 3);
    }
    private function makeClientId()
    {
        if ($this->currentClientId) {
            return $this->currentClientId;
        }
        if ($this->getChance() > 90) {
            // completely new visitor
            return md5(time());
        }
        // returning visitor
        $seed = $this->getRandomElement($this->visitorIdSeeds);
        $this->currentClientId = md5($seed);
        return $this->currentClientId;
    }
    private function send($params)
    {
        $requestBody = http_build_query($params);
        $this->logger->debug('Sending params: {params}', ['params' => $params]);
        Http::sendHttpRequestBy(Http::getTransportMethod(), 'http://www.google-analytics.com/collect', $timeout = 5, $userAgent = null, $destinationPath = null, $file = null, $followDepth = 0, $acceptLanguage = null, $acceptInvalidSslCert = null, $byteRange = \false, $getExtendedInfo = \false, $httpMethod = 'POST', $httpUsername = null, $httpPassword = null, $requestBody);
    }
    private function getRandomPageDownloadTime()
    {
        return random_int(0, 5 * 1000);
    }
}
