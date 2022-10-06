<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
class IncompatibleSelfOutTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		$selfOutType = $method->getSelfOutType();
		if ($selfOutType === null) {
			return [];
		}

		$classReflection = $method->getDeclaringClass();
		$classType = new ObjectType($classReflection->getName(), null, $classReflection);

		if (!$classType->isSuperTypeOf($selfOutType)->no()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Out type %s is not compatible with %s.',
				$selfOutType->describe(VerbosityLevel::precise()),
				$classType->describe(VerbosityLevel::precise()),
			))->build(),
		];
	}

}
