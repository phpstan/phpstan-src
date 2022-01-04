<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\Assign>
 */
class AccessStaticPropertiesInAssignRule implements Rule
{

	public function __construct(private AccessStaticPropertiesRule $accessStaticPropertiesRule)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Assign::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->var instanceof Node\Expr\StaticPropertyFetch) {
			return [];
		}

		return $this->accessStaticPropertiesRule->processNode($node->var, $scope);
	}

}
