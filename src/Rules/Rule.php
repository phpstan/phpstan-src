<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * This is the interface custom rules implement. To register it in the configuration file
 * use the `phpstan.rules.rule` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\MyRule
 *		tags:
 *			- phpstan.rules.rule
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/rules
 *
 * @api
 * @phpstan-template TNodeType of Node
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
