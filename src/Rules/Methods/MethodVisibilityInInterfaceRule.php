<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/** @implements Rule<InClassMethodNode> */
class MethodVisibilityInInterfaceRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();

		if ($method->isPublic()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		if ($classReflection === null) {
			return [];
		}

		if (!$classReflection->isInterface()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Method %s::%s() cannot use non-public visibility in interface.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->nonIgnorable()->build(),
		];
	}

}
