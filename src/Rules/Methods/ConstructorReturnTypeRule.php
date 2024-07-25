<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
final class ConstructorReturnTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$methodNode = $node->getOriginalNode();
		if ($scope->isInTrait()) {
			$originalMethodName = $methodNode->getAttribute('originalTraitMethodName');
			if (
				$originalMethodName === '__construct'
				&& $methodNode->returnType !== null
			) {
				return [
					RuleErrorBuilder::message(sprintf('Original constructor of trait %s has a return type.', $scope->getTraitReflection()->getDisplayName()))
						->identifier('constructor.returnType')
						->nonIgnorable()
						->build(),
				];
			}
		}
		if (!$classReflection->hasConstructor()) {
			return [];
		}

		$constructorReflection = $classReflection->getConstructor();
		$methodReflection = $node->getMethodReflection();
		if ($methodReflection->getName() !== $constructorReflection->getName()) {
			return [];
		}

		if ($methodNode->returnType === null) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('Constructor of class %s has a return type.', $classReflection->getDisplayName()))
				->identifier('constructor.returnType')
				->nonIgnorable()
				->build(),
		];
	}

}
