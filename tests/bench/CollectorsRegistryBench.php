<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Collectors\Registry;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

final class CollectorsRegistryBench
{

	private ?Registry $registry = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);
		// the rules registry asked about the node type first
		(new Registry([]))->getCollectors(MethodCall::class);
		$this->registry = new Registry([]);
	}

	/**
	 * The first time the collectors registry is asked about a node type.
	 *
	 * @return mixed[]
	 */
	#[Expected(instructions: '3.452K', cycles: '21.94K', memory: '376B')]
	public function benchGetCollectors(): array
	{
		if ($this->registry === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->registry->getCollectors(MethodCall::class);
	}

}
