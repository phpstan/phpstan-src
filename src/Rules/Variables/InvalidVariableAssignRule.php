<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use ArrayAccess;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function is_string;

/**
 * @implements Rule<VariableAssignNode>
 */
#[RegisteredRule(level: 0)]
final class InvalidVariableAssignRule implements Rule
{

	public function getNodeType(): string
	{
		return VariableAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$variable = $node->getVariable();
		if (!is_string($variable->name)) {
			return [];
		}

		if ($variable->name === 'this') {
			$expr = $node->getAssignedExpr();
			$type = $scope->getType($expr);

			if ((new ObjectType(ArrayAccess::class))->isSuperTypeOf($type)->yes()) {
				return [];
			}

			return [
				RuleErrorBuilder::message('Cannot re-assign $this.')
					->identifier('assign.this')
					->nonIgnorable()
					->build(),
			];
		}

		return [];
	}

}
