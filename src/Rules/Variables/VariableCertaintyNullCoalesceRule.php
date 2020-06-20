<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NullType;

/**
 * @implements \PHPStan\Rules\Rule<Node\Expr>
 */
class VariableCertaintyNullCoalesceRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return Node\Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof Node\Expr\AssignOp\Coalesce) {
			$var = $node->var;
			$description = '??=';
		} elseif ($node instanceof Node\Expr\BinaryOp\Coalesce) {
			$var = $node->left;
			$description = '??';
		} else {
			return [];
		}

		$isSubNode = false;
		while (
			$var instanceof Node\Expr\ArrayDimFetch
			|| $var instanceof Node\Expr\PropertyFetch
			|| (
				$var instanceof Node\Expr\StaticPropertyFetch
				&& $var->class instanceof Node\Expr
			)
		) {
			if ($var instanceof Node\Expr\StaticPropertyFetch) {
				$var = $var->class;
			} else {
				$var = $var->var;
			}
			$isSubNode = true;
		}

		if (!$var instanceof Node\Expr\Variable || !is_string($var->name)) {
			return [];
		}

		$certainty = $scope->hasVariableType($var->name);
		if ($certainty->no()) {
			return [RuleErrorBuilder::message(sprintf(
				'Variable $%s on left side of %s is never defined.',
				$var->name,
				$description
			))->build()];
		} elseif ($certainty->yes() && !$isSubNode) {
			$variableType = $scope->getVariableType($var->name);
			if ($variableType->isSuperTypeOf(new NullType())->no()) {
				return [RuleErrorBuilder::message(sprintf(
					'Variable $%s on left side of %s always exists and is not nullable.',
					$var->name,
					$description
				))->build()];
			} elseif ((new NullType())->isSuperTypeOf($variableType)->yes()) {
				return [RuleErrorBuilder::message(sprintf(
					'Variable $%s on left side of %s is always null.',
					$var->name,
					$description
				))->build()];
			}
		}

		return [];
	}

}
