<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AutowiredServices;

use PHPStan\DependencyInjection\NonAutowiredService;

#[NonAutowiredService(name: 'testedDiscoveredNonAutowiredService')]
final class TestedDiscoveredNonAutowiredService
{

}
