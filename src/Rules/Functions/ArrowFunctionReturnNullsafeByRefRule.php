<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\ArrowFunction>
 */
class ArrowFunctionReturnNullsafeByRefRule implements Rule
{

	public function __construct(private NullsafeCheck $nullsafeCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\ArrowFunction::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->byRef) {
			return [];
		}

		if (!$this->nullsafeCheck->containsNullSafe($node->expr)) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Nullsafe cannot be returned by reference.')
				->nonIgnorable()
				->identifier('nullsafe.byRef')
				->build(),
		];
	}

}
