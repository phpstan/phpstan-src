<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Collectors\Collector;

/**
 * CollectedDataEmitter allows rules to emit collected data directly, without having to write
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
 * The scope passed to Rule::processNode() implements CollectedDataEmitter. Keep the native parameter type
 * as Scope so the rule is compatible with the distributed PHPStan PHAR. Declare
 * `@param Scope&CollectedDataEmitter $scope` in the method PHPDoc:
 *
 * ```php
 * public function processNode(Node $node, Scope $scope): array
 * {
 *     $scope->emitCollectedData(MyCollector::class, ['some', 'data']);
 *
 *     return [];
 * }
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
