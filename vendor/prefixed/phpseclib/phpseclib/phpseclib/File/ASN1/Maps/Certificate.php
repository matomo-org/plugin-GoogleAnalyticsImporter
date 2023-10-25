<?php

/**
 * Certificate
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
 * Certificate
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Certificate
{
    const MAP = ['type' => ASN1::TYPE_SEQUENCE, 'children' => ['tbsCertificate' => \phpseclib3\File\ASN1\Maps\TBSCertificate::MAP, 'signatureAlgorithm' => \phpseclib3\File\ASN1\Maps\AlgorithmIdentifier::MAP, 'signature' => ['type' => ASN1::TYPE_BIT_STRING]]];
}
