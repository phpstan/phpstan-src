<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Classes\ConsistentConstructorHelper;
use PHPStan\Rules\Rule;
use function array_merge;
use function strtolower;

/** @implements Rule<InClassMethodNode> */
#[RegisteredRule(level: 0)]
final class ConsistentConstructorRule implements Rule
{

	public function __construct(
		private ConsistentConstructorHelper $consistentConstructorHelper,
		private MethodParameterComparisonHelper $methodParameterComparisonHelper,
		private MethodVisibilityComparisonHelper $methodVisibilityComparisonHelper,
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

		$parentConstructor = $this->consistentConstructorHelper->findConsistentConstructor($parent);
		if ($parentConstructor === null) {
			return [];
		}

		return array_merge(
			$this->methodParameterComparisonHelper->compare($parentConstructor, $parentConstructor->getDeclaringClass(), $method, true),
			$this->methodVisibilityComparisonHelper->compare($parentConstructor, $parentConstructor->getDeclaringClass(), $method),
		);
	}

}
