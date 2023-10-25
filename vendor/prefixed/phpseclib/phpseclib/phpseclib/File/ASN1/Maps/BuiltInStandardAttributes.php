<?php

/**
 * BuiltInStandardAttributes
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
 * BuiltInStandardAttributes
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class BuiltInStandardAttributes
{
    const MAP = ['type' => ASN1::TYPE_SEQUENCE, 'children' => ['country-name' => ['optional' => \true] + \phpseclib3\File\ASN1\Maps\CountryName::MAP, 'administration-domain-name' => ['optional' => \true] + \phpseclib3\File\ASN1\Maps\AdministrationDomainName::MAP, 'network-address' => ['constant' => 0, 'optional' => \true, 'implicit' => \true] + \phpseclib3\File\ASN1\Maps\NetworkAddress::MAP, 'terminal-identifier' => ['constant' => 1, 'optional' => \true, 'implicit' => \true] + \phpseclib3\File\ASN1\Maps\TerminalIdentifier::MAP, 'private-domain-name' => ['constant' => 2, 'optional' => \true, 'explicit' => \true] + \phpseclib3\File\ASN1\Maps\PrivateDomainName::MAP, 'organization-name' => ['constant' => 3, 'optional' => \true, 'implicit' => \true] + \phpseclib3\File\ASN1\Maps\OrganizationName::MAP, 'numeric-user-identifier' => ['constant' => 4, 'optional' => \true, 'implicit' => \true] + \phpseclib3\File\ASN1\Maps\NumericUserIdentifier::MAP, 'personal-name' => ['constant' => 5, 'optional' => \true, 'implicit' => \true] + \phpseclib3\File\ASN1\Maps\PersonalName::MAP, 'organizational-unit-names' => ['constant' => 6, 'optional' => \true, 'implicit' => \true] + \phpseclib3\File\ASN1\Maps\OrganizationalUnitNames::MAP]];
}
