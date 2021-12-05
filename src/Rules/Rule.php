<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @api
 * @phpstan-template TNodeType of \PhpParser\Node
 */
interface Rule
{

	/**
	 * @phpstan-return class-string<TNodeType>
	 */
	public function getNodeType(): string;

	/**
	 * @phpstan-param TNodeType $node
	 * @return (string|RuleError)[] errors
	 */
	public function processNode(Node $node, Scope $scope): array;

}
