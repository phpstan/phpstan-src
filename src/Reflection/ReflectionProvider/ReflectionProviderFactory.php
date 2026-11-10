<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;

#[AutowiredService(name: 'reflectionProviderFactory')]
final class ReflectionProviderFactory
{

	public function __construct(
		#[AutowiredParameter(ref: '@betterReflectionProvider')]
		private ReflectionProvider $staticReflectionProvider,
		private ValueDependencyCollector $valueDependencyCollector,
	)
	{
	}

	public function create(): ReflectionProvider
	{
		return new MemoizingReflectionProvider($this->staticReflectionProvider, $this->valueDependencyCollector);
	}

}
