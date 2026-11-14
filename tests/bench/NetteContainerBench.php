<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\DependencyInjection\Container;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

final class NetteContainerBench
{

	private ?Container $container = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$this->container = BenchContainer::create($this->scratch);
		$this->container->getParameter('tmpDir');
	}

	#[Expected(instructions: '1.051K', cycles: '9.651K', memory: '0B')]
	public function benchGetParameter(): mixed
	{
		if ($this->container === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->container->getParameter('tmpDir');
	}

	#[Expected(instructions: '1.529K', cycles: '12.22K', memory: '0B')]
	public function benchHasParameter(): bool
	{
		if ($this->container === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->container->hasParameter('tmpDir');
	}

}
