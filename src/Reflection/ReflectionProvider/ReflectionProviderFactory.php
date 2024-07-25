<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;

final class ReflectionProviderFactory
{

	public function __construct(
		private ReflectionProvider $staticReflectionProvider,
	)
	{
	}

	public function create(): ReflectionProvider
	{
		return new MemoizingReflectionProvider($this->staticReflectionProvider);
	}

}
