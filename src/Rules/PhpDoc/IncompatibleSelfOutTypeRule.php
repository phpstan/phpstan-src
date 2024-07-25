<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
final class IncompatibleSelfOutTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();
		$selfOutType = $method->getSelfOutType();

		if ($selfOutType === null) {
			return [];
		}

		$classReflection = $method->getDeclaringClass();
		$classType = new ObjectType($classReflection->getName(), null, $classReflection);

		if ($classType->isSuperTypeOf($selfOutType)->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Self-out type %s of method %s::%s is not subtype of %s.',
				$selfOutType->describe(VerbosityLevel::precise()),
				$classReflection->getName(),
				$method->getName(),
				$classType->describe(VerbosityLevel::precise()),
			))->identifier('selfOut.type')->build(),
		];
	}

}
