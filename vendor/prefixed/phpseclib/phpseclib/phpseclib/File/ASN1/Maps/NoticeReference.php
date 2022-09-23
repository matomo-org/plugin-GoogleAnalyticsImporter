<?php

/**
 * NoticeReference
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
 * NoticeReference
 *
 * @package ASN1
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
abstract class NoticeReference
{
    const MAP = ['type' => ASN1::TYPE_SEQUENCE, 'children' => ['organization' => \Matomo\Dependencies\GoogleAnalyticsImporter\phpseclib3\File\ASN1\Maps\DisplayText::MAP, 'noticeNumbers' => ['type' => ASN1::TYPE_SEQUENCE, 'min' => 1, 'max' => 200, 'children' => ['type' => ASN1::TYPE_INTEGER]]]];
}
