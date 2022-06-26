<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @phpstan-template TNodeType of Node
 */
interface Collector
{

	/**
	 * @phpstan-return class-string<TNodeType>
	 */
	public function getNodeType(): string;

	/**
	 * @phpstan-param TNodeType $node
	 * @return mixed Collected data
	 */
	public function processNode(Node $node, Scope $scope);

}
