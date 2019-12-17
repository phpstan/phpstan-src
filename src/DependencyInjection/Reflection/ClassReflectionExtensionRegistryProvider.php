<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\Reflection;

use PHPStan\Reflection\ClassReflectionExtensionRegistry;

interface ClassReflectionExtensionRegistryProvider
{

	public function getRegistry(): ClassReflectionExtensionRegistry;

}
