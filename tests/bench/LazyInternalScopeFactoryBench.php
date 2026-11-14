<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Analyser\InternalScopeFactory;
use PHPStan\Analyser\InternalScopeFactoryFactory;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\ScopeContext;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;

/**
 * Every change of a scope creates a new one through the factory.
 */
final class LazyInternalScopeFactoryBench
{

	private const SCOPES = 256;

	private ?InternalScopeFactory $factory = null;

	private ?ScopeContext $context = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->factory = $container->getByType(InternalScopeFactoryFactory::class)->create(null);
		$this->context = ScopeContext::create(__FILE__);
		$this->factory->create($this->context);
	}

	public function benchCreate(): MutatingScope
	{
		if ($this->factory === null || $this->context === null) {
			throw new LogicException('boot() did not run.');
		}

		$scope = $this->factory->create($this->context);
		for ($i = 1; $i < self::SCOPES; $i++) {
			$scope = $this->factory->create($this->context);
		}

		return $scope;
	}

}
