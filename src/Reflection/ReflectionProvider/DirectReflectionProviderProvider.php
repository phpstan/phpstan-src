<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;

final class DirectReflectionProviderProvider implements ReflectionProviderProvider
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getReflectionProvider(): ReflectionProvider
	{
		return $this->reflectionProvider;
	}

}
