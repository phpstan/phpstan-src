<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PHPStan\DependencyInjection as DI;

/**
 * Refers to the attribute through an aliased namespace import, which neither spells out the
 * attribute's short name nor its fully qualified one.
 */
#[DI\AutowiredService(name: 'testedDiscoveredAliasedService')]
final class TestedDiscoveredAliasedService
{

}
