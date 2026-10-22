<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Methods\ParentMethodHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use Throwable;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
final class MethodThrowTypeCovarianceRule implements Rule
{

	public function __construct(
		private ParentMethodHelper $parentMethodHelper,
		private bool $implicitThrows,
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
		$methodName = $method->getName();
		if ($methodName === '__construct') {
			return [];
		}

		if ($method->isPrivate()) {
			return [];
		}

		$throwType = $method->getThrowType();
		if ($throwType === null) {
			if ($this->implicitThrows) {
				$throwType = new ObjectType(Throwable::class);
			} else {
				$throwType = new VoidType();
			}
		}

		if ($throwType->isVoid()->yes()) {
			return [];
		}

		$errors = [];
		foreach ($this->parentMethodHelper->collectParentMethods($methodName, $method->getDeclaringClass()) as [$parentMethod, $parentMethodDeclaringClass]) {
			$parentThrowType = $parentMethod->getThrowType();
			$explicitParentThrowsVoid = true;
			if ($parentThrowType === null) {
				if ($this->implicitThrows) {
					$parentThrowType = new ObjectType(Throwable::class);
				} else {
					$parentThrowType = new VoidType();
					$explicitParentThrowsVoid = false;
				}
			} else {
				$explicitParentThrowsVoid = $parentThrowType->isVoid()->yes();
			}

			if ($parentThrowType->isVoid()->yes()) {
				if ($explicitParentThrowsVoid) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Method %s::%s() should not throw %s because parent method %s::%s() has PHPDoc tag @throws void.',
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
						$throwType->describe(VerbosityLevel::typeOnly()),
						$parentMethodDeclaringClass->getDisplayName(),
						$parentMethod->getName(),
					))->identifier('throws.shouldBeVoid')->build();
				} else {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Method %s::%s() should not throw %s because parent method %s::%s() does not have PHPDoc tag @throws.',
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
						$throwType->describe(VerbosityLevel::typeOnly()),
						$parentMethodDeclaringClass->getDisplayName(),
						$parentMethod->getName(),
					))->identifier('throws.notCovariantWithImplicitVoid')->build();
				}
				continue;
			}

			if ($parentThrowType->isSuperTypeOf($throwType)->yes()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @throws type %s of method %s::%s() should be covariant with PHPDoc @throws type %s of method %s::%s().',
				$throwType->describe(VerbosityLevel::typeOnly()),
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$parentThrowType->describe(VerbosityLevel::typeOnly()),
				$parentMethodDeclaringClass->getDisplayName(),
				$parentMethod->getName(),
			))->identifier('throws.notCovariant')->build();
		}

		return $errors;
	}

}
