<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
class IllegalConstructorMethodCallRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier || $node->name->name !== '__construct') {
			return [];
		}

		if ($this->isCollectCallingConstructor($node, $scope)) {
			return [];
		}

		return [
			RuleErrorBuilder::message('__construct() should not be called outside constructor.')
				->build(),
		];
	}

	private function isCollectCallingConstructor(Node $node, Scope $scope): bool
	{
		if (!$node instanceof Node\Expr\MethodCall) {
			return true;
		}
		// __construct should be called from inside constructor
		if ($scope->getFunction() !== null && $scope->getFunction()->getName() !== '__construct') {
			return false;
		}
		// In constructor, method call is allowed with 'this';
		return $node->var instanceof Node\Expr\Variable && $node->var->name === 'this';
	}

}
