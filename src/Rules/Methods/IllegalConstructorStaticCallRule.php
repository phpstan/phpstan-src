<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function in_array;
use function strtolower;

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
		if (!$node->name instanceof Node\Identifier || $node->name->toLowerString() !== '__construct') {
			return [];
		}

		if ($this->isCollectCallingConstructor($node, $scope)) {
			return [];
		}

		return [
			RuleErrorBuilder::message('Static call to __construct() is only allowed on a parent class in the constructor.')
				->identifier('constructor.call')
				->build(),
		];
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

		if (!$scope->isInClass()) {
			return false;
		}

		if (!$node->class instanceof Node\Name) {
			return false;
		}

		$parentClasses = array_map(static fn (string $name) => strtolower($name), $scope->getClassReflection()->getParentClassesNames());

		return in_array(strtolower($scope->resolveName($node->class)), $parentClasses, true);
	}

}
