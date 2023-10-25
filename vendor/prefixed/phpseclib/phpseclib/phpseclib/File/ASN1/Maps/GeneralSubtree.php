<?php

/**
 * GeneralSubtree
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
 * GeneralSubtree
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class GeneralSubtree
{
    const MAP = ['type' => ASN1::TYPE_SEQUENCE, 'children' => ['base' => \phpseclib3\File\ASN1\Maps\GeneralName::MAP, 'minimum' => ['constant' => 0, 'optional' => \true, 'implicit' => \true, 'default' => '0'] + \phpseclib3\File\ASN1\Maps\BaseDistance::MAP, 'maximum' => ['constant' => 1, 'optional' => \true, 'implicit' => \true] + \phpseclib3\File\ASN1\Maps\BaseDistance::MAP]];
}
