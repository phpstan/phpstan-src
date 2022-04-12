<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use function in_array;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
class IllegalConstructorStaticCallRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier || $node->name->name !== '__construct') {
			return [];
		}

		return $this->isCollectCallingConstructor($node, $scope)
			? []
			: ['__construct() should not be called outside constructor.'];
	}

	private function isCollectCallingConstructor(Node $node, Scope $scope): bool
	{
		if (!$node instanceof Node\Expr\StaticCall) {
			return true;
		}
		// __construct should be called from inside constructor
		if ($scope->getFunction() !== null && $scope->getFunction()->getName() !== '__construct') {
			return false;
		}
		// In constructor, static call is allowed with 'self' or 'parent;
		return $node->class instanceof Node\Name && in_array($node->class->getFirst(), ['self', 'parent'], true);
	}

}
