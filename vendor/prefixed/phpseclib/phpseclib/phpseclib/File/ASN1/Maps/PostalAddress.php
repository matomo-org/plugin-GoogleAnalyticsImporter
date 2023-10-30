<?php

/**
 * PostalAddress
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\File\ASN1\Maps;

use Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\File\ASN1;
/**
 * PostalAddress
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PostalAddress
{
    const MAP = ['type' => ASN1::TYPE_SEQUENCE, 'optional' => \true, 'min' => 1, 'max' => -1, 'children' => DirectoryString::MAP];
}