<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NullType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Isset_>
 */
class VariableCertaintyInIssetRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\Isset_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];
		foreach ($node->vars as $var) {
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

			if (!$var instanceof Node\Expr\Variable || !is_string($var->name) || $var->name === '_SESSION') {
				continue;
			}

			$certainty = $scope->hasVariableType($var->name);
			if ($certainty->no()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Variable $%s in isset() is never defined.',
					$var->name
				))->build();
			} elseif ($certainty->yes() && !$isSubNode) {
				$variableType = $scope->getVariableType($var->name);
				if ($variableType->isSuperTypeOf(new NullType())->no()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Variable $%s in isset() always exists and is not nullable.',
						$var->name
					))->build();
				} elseif ((new NullType())->isSuperTypeOf($variableType)->yes()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Variable $%s in isset() is always null.',
						$var->name
					))->build();
				}
			}
		}

		return $messages;
	}

}
