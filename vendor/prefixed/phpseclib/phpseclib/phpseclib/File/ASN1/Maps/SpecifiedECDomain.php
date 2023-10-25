<?php

/**
 * SpecifiedECDomain
 *
 * From: http://www.secg.org/sec1-v2.pdf#page=109
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */
namespace phpseclib3\File\ASN1\Maps;

use phpseclib3\File\ASN1;
/**
 * SpecifiedECDomain
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class SpecifiedECDomain
{
    const MAP = ['type' => ASN1::TYPE_SEQUENCE, 'children' => ['version' => ['type' => ASN1::TYPE_INTEGER, 'mapping' => [1 => 'ecdpVer1', 'ecdpVer2', 'ecdpVer3']], 'fieldID' => \phpseclib3\File\ASN1\Maps\FieldID::MAP, 'curve' => \phpseclib3\File\ASN1\Maps\Curve::MAP, 'base' => \phpseclib3\File\ASN1\Maps\ECPoint::MAP, 'order' => ['type' => ASN1::TYPE_INTEGER], 'cofactor' => ['type' => ASN1::TYPE_INTEGER, 'optional' => \true], 'hash' => ['optional' => \true] + \phpseclib3\File\ASN1\Maps\HashAlgorithm::MAP]];
}
