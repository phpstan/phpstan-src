<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;

interface ReflectionProviderProvider
{

	public function getReflectionProvider(): ReflectionProvider;

}
