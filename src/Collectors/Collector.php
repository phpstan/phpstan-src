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
 * @phpstan-template-covariant TNodeType of Node
 * @phpstan-template-covariant TValue
 */
interface Collector
{

	/**
	 * @phpstan-return class-string<TNodeType>
	 */
	public function getNodeType(): string;

	/**
	 * @phpstan-param TNodeType $node
	 * @return TValue|null Collected data
	 */
	public function processNode(Node $node, Scope $scope);

}
