<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Collectors\Collector;

/**
 * The interface CollectedDataEmitter can be typehinted in 2nd parameter of Rule::processNode():
 *
 * ```php
 * public function processNode(Node $node, Scope&CollectedDataEmitter $scope): array
 * ```
 *
 * It allows rules to emit collected data directly, without having to write
 * a separate complex Collector class. The emitted data is aggregated the same way
 * as data from Collectors and can be consumed by rules registered
 * for CollectedDataNode.
 *
 * The actual MyCollector class in the example has to exist, to verify
 * the data type statically, and to identify the collected data.
 *
 * The referenced MyCollector class should NOT be registered
 * as a collector, unless you also want it to collect data on its own.
 *
 * ```php
 * $scope->emitCollectedData(MyCollector::class, ['some', 'data']);
 * ```
 *
 * @api
 */
interface CollectedDataEmitter
{

	/**
	 * @template TCollector of Collector<Node, mixed>
	 * @param class-string<TCollector> $collectorType
	 * @param template-type<TCollector, Collector, 'TValue'> $data
	 */
	public function emitCollectedData(string $collectorType, mixed $data): void;

}
