<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;

final class SetterReflectionProviderProvider implements ReflectionProviderProvider
{

	private ReflectionProvider $reflectionProvider;

	public function setReflectionProvider(ReflectionProvider $reflectionProvider): void
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getReflectionProvider(): ReflectionProvider
	{
		return $this->reflectionProvider;
	}

}
