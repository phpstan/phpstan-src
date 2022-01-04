<?php declare(strict_types = 1);

namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;
use function count;

class ReflectionProviderFactory
{

	public function __construct(
		private ReflectionProvider $runtimeReflectionProvider,
		private ReflectionProvider $staticReflectionProvider,
		private bool $disableRuntimeReflectionProvider,
	)
	{
	}

	public function create(): ReflectionProvider
	{
		$providers = [];

		if (!$this->disableRuntimeReflectionProvider) {
			$providers[] = $this->runtimeReflectionProvider;
		}

		$providers[] = $this->staticReflectionProvider;

		return new MemoizingReflectionProvider(count($providers) === 1 ? $providers[0] : new ChainReflectionProvider($providers));
	}

}
