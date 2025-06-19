<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<PropertyAssignNode>
 */
#[RegisteredRule(level: 0)]
final class AccessPropertiesInAssignRule implements Rule
{

	public function __construct(private AccessPropertiesCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return PropertyAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getPropertyFetch() instanceof Node\Expr\PropertyFetch) {
			return [];
		}

		if ($node->isAssignOp()) {
			return [];
		}

		return $this->check->check($node->getPropertyFetch(), $scope, true);
	}

}
