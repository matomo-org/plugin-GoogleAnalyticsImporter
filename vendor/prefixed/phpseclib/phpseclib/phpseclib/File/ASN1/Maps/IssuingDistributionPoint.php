<?php

/**
 * IssuingDistributionPoint
 *
 * PHP version 5
 *
 * @category  File
 * @package   ASN1
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\File\ASN1\Maps;

use Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\File\ASN1;
/**
 * IssuingDistributionPoint
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class IssuingDistributionPoint
{
    const MAP = ['type' => ASN1::TYPE_SEQUENCE, 'children' => ['distributionPoint' => ['constant' => 0, 'optional' => \true, 'explicit' => \true] + \Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\File\ASN1\Maps\DistributionPointName::MAP, 'onlyContainsUserCerts' => ['type' => ASN1::TYPE_BOOLEAN, 'constant' => 1, 'optional' => \true, 'default' => \false, 'implicit' => \true], 'onlyContainsCACerts' => ['type' => ASN1::TYPE_BOOLEAN, 'constant' => 2, 'optional' => \true, 'default' => \false, 'implicit' => \true], 'onlySomeReasons' => ['constant' => 3, 'optional' => \true, 'implicit' => \true] + \Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\File\ASN1\Maps\ReasonFlags::MAP, 'indirectCRL' => ['type' => ASN1::TYPE_BOOLEAN, 'constant' => 4, 'optional' => \true, 'default' => \false, 'implicit' => \true], 'onlyContainsAttributeCerts' => ['type' => ASN1::TYPE_BOOLEAN, 'constant' => 5, 'optional' => \true, 'default' => \false, 'implicit' => \true]]];
}
