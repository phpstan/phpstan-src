<?php declare(strict_types = 1);

namespace AttributeServicesFixtures\Unloadable;

use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class UnloadableService extends MissingParentClass
{

}
