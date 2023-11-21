<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/** @implements Rule<InClassMethodNode> */
class AbstractProtectedMethodRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();

		if ($method->isPrivate() || $method->isPublic()) {
			return [];
		}

		if (!$method->isAbstract()->yes()) {
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
				'Protected method %s::%s() cannot be abstract.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->nonIgnorable()->build(),
		];
	}

}
