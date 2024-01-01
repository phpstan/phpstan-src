<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedFunctionParametersCheck;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function count;
use function is_string;

/**
 * @implements Rule<Node\Expr\Closure>
 */
class UnusedClosureUsesRule implements Rule
{

	public function __construct(private UnusedFunctionParametersCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Closure::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->uses) === 0) {
			return [];
		}

		return $this->check->getUnusedParameters(
			$scope,
			array_map(static function (Node\ClosureUse $use): string {
				if (!is_string($use->var->name)) {
					throw new ShouldNotHappenException();
				}
				return $use->var->name;
			}, $node->uses),
			$node->stmts,
			'Anonymous function has an unused use $%s.',
			'closure.unusedUse',
		);
	}

}
