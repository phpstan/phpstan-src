<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ExtensionClassHelper;

#[AutowiredService(factory: '@PHPStan\Collectors\RegistryFactory::create')]
final class Registry
{

	/** @var Collector[][] */
	private array $collectors = [];

	/** @var Collector[][] */
	private array $cache = [];

	/**
	 * @param Collector[] $collectors
	 */
	public function __construct(
		array $collectors,
		private ReflectionProvider $reflectionProvider,
	)
	{
		foreach ($collectors as $collector) {
			$this->collectors[$collector->getNodeType()][] = $collector;
		}
	}

	/**
	 * @template TNodeType of Node
	 * @param class-string<TNodeType> $nodeType
	 * @return array<Collector<TNodeType, mixed>>
	 */
	public function getCollectors(string $nodeType): array
	{
		if (!isset($this->cache[$nodeType])) {
			$parentNodeTypes = ExtensionClassHelper::getExtensionClassNames($this->reflectionProvider, $nodeType);

			$collectors = [];
			foreach ($parentNodeTypes as $parentNodeType) {
				foreach ($this->collectors[$parentNodeType] ?? [] as $collector) {
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

}
