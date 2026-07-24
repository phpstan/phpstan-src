<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use function class_implements;
use function class_parents;

#[AutowiredService]
final class Registry
{

	public const COLLECTOR_TAG = 'phpstan.collector';

	/** @var Collector[][]|null */
	private ?array $collectorsByNodeType = null;

	/** @var Collector[][] */
	private array $cache = [];

	/**
	 * @param ExtensionsCollection<Collector<Node, mixed>> $collectors
	 */
	public function __construct(
		#[AutowiredExtensions(interface: Collector::class)]
		private ExtensionsCollection $collectors,
	)
	{
	}

	/**
	 * @template TNodeType of Node
	 * @param class-string<TNodeType> $nodeType
	 * @return array<Collector<TNodeType, mixed>>
	 */
	public function getCollectors(string $nodeType): array
	{
		if (!isset($this->cache[$nodeType])) {
			$parentNodeTypes = [$nodeType] + class_parents($nodeType) + class_implements($nodeType);

			$collectors = [];
			$collectorsFromContainer = $this->getCollectorsByNodeType();
			foreach ($parentNodeTypes as $parentNodeType) {
				foreach ($collectorsFromContainer[$parentNodeType] ?? [] as $collector) {
					$collectors[] = $collector;
				}
			}

			$this->cache[$nodeType] = $collectors;
		}

		/**
		 * @var array<Collector<TNodeType, mixed>> $selectedCollectors
		 */
		$selectedCollectors = $this->cache[$nodeType];

		return $selectedCollectors;
	}

	/**
	 * @return Collector[][]
	 */
	private function getCollectorsByNodeType(): array
	{
		if ($this->collectorsByNodeType !== null) {
			return $this->collectorsByNodeType;
		}

		$collectors = [];
		foreach ($this->collectors->getAll() as $collector) {
			$collectors[$collector->getNodeType()][] = $collector;
		}

		return $this->collectorsByNodeType = $collectors;
	}

}
