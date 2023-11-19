<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Rules\Rule;
use function strtolower;

/** @implements Rule<InClassMethodNode> */
class ConsistentConstructorRule implements Rule
{

	public function __construct(
		private MethodParameterComparisonHelper $methodParameterComparisonHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		if (strtolower($method->getName()) !== '__construct') {
			return [];
		}

		$parent = $method->getDeclaringClass()->getParentClass();

		if ($parent === null) {
			return [];
		}

		if ($parent->hasConstructor()) {
			$parentConstructor = $parent->getConstructor();
		} else {
			$parentConstructor = new DummyConstructorReflection($parent);
		}

		if (! $parentConstructor->getDeclaringClass()->hasConsistentConstructor()) {
			return [];
		}

		return $this->methodParameterComparisonHelper->compare($parentConstructor, $parentConstructor->getDeclaringClass(), $method, true);
	}

}
