<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use function class_implements;
use function class_parents;

class Registry
{

	/** @var Collector[][] */
	private array $collectors = [];

	/** @var Collector[][] */
	private array $cache = [];

	/**
	 * @param Collector[] $collectors
	 */
	public function __construct(array $collectors)
	{
		foreach ($collectors as $collector) {
			$this->collectors[$collector->getNodeType()][] = $collector;
		}
	}

	/**
	 * @template TNodeType of Node
	 * @phpstan-param class-string<TNodeType> $nodeType
	 * @param Node $nodeType
	 * @phpstan-return array<Collector<TNodeType, mixed>>
	 * @return Collector[]
	 */
	public function getCollectors(string $nodeType): array
	{
		if (!isset($this->cache[$nodeType])) {
			$parents = class_parents($nodeType);
			if ($parents === false) {
				$parents = [];
			}

			$implements = class_implements($nodeType);
			if ($implements === false) {
				$implements = [];
			}

			$parentNodeTypes = [$nodeType] + $parents + $implements;

			$collectors = [];
			foreach ($parentNodeTypes as $parentNodeType) {
				foreach ($this->collectors[$parentNodeType] ?? [] as $collector) {
					$collectors[] = $collector;
				}
			}

			$this->cache[$nodeType] = $collectors;
		}

		/**
		 * @phpstan-var array<Collector<TNodeType, mixed>> $selectedCollectors
		 * @var Collector[] $selectedCollectors
		 */
		$selectedCollectors = $this->cache[$nodeType];

		return $selectedCollectors;
	}

}
