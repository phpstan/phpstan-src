<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * This is the interface custom collectors implement. To register it in the configuration file
 * use the `phpstan.collector` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\MyCollector
 *		tags:
 *			- phpstan.collector
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/collectors
 *
 * @api
 * @template-covariant TNodeType of Node
 * @template-covariant TValue
 */
interface Collector
{

	/**
	 * @return class-string<TNodeType>
	 */
	public function getNodeType(): string;

	/**
	 * @param TNodeType $node
	 * @return TValue|null Collected data
	 */
	public function processNode(Node $node, Scope $scope);

}
