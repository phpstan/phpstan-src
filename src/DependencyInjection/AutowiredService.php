<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Attribute;

#[Attribute(flags: Attribute::TARGET_CLASS)]
final class AutowiredService
{

}
