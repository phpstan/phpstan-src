<?php

namespace SnmpSet;

use SNMP;
use function PHPStan\Testing\assertType;

class Foo
{
    public function testSnmpGet(SNMP $snmp)
    {
        $result = $snmp->get('SNMPv2-MIB::sysContact.0');
        assertType('string|false', $result);

        $result = $snmp->get(['SNMPv2-MIB::sysContact.0', 'SNMPv2-MIB::sysDescr.0']);
        assertType('array<string, string>|false', $result);
    }
}
