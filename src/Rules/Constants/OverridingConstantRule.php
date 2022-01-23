<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
class OverridingConstantRule implements Rule
{

	public function __construct(
		private bool $checkPhpDocMethodSignatures,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$errors = [];
		foreach ($node->consts as $const) {
			$constantName = $const->name->toString();
			$errors = array_merge($errors, $this->processSingleConstant($scope->getClassReflection(), $constantName));
		}

		return $errors;
	}

	/**
	 * @return RuleError[]
	 */
	private function processSingleConstant(ClassReflection $classReflection, string $constantName): array
	{
		$prototype = $this->findPrototype($classReflection, $constantName);
		if (!$prototype instanceof ClassConstantReflection) {
			return [];
		}

		$constantReflection = $classReflection->getConstant($constantName);
		if (!$constantReflection instanceof ClassConstantReflection) {
			return [];
		}

		$errors = [];
		if ($prototype->isFinal()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s overrides final constant %s::%s.',
				$classReflection->getDisplayName(),
				$constantReflection->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$prototype->getName(),
			))->nonIgnorable()->build();
		}

		if ($prototype->isPublic()) {
			if (!$constantReflection->isPublic()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s constant %s::%s overriding public constant %s::%s should also be public.',
					$constantReflection->isPrivate() ? 'Private' : 'Protected',
					$constantReflection->getDeclaringClass()->getDisplayName(),
					$constantReflection->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$prototype->getName(),
				))->nonIgnorable()->build();
			}
		} elseif ($constantReflection->isPrivate()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Private constant %s::%s overriding protected constant %s::%s should be protected or public.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantReflection->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$prototype->getName(),
			))->nonIgnorable()->build();
		}

		if (!$this->checkPhpDocMethodSignatures) {
			return $errors;
		}

		if (!$prototype->hasPhpDocType()) {
			return $errors;
		}

		if (!$constantReflection->hasPhpDocType()) {
			return $errors;
		}

		if (!$prototype->getValueType()->isSuperTypeOf($constantReflection->getValueType())->yes()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Type %s of constant %s::%s is not covariant with type %s of constant %s::%s.',
				$constantReflection->getValueType()->describe(VerbosityLevel::value()),
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantReflection->getName(),
				$prototype->getValueType()->describe(VerbosityLevel::value()),
				$prototype->getDeclaringClass()->getDisplayName(),
				$prototype->getName(),
			))->build();
		}

		return $errors;
	}

	private function findPrototype(ClassReflection $classReflection, string $constantName): ?ConstantReflection
	{
		foreach ($classReflection->getImmediateInterfaces() as $immediateInterface) {
			if ($immediateInterface->hasConstant($constantName)) {
				return $immediateInterface->getConstant($constantName);
			}
		}

		$parentClass = $classReflection->getParentClass();
		if ($parentClass === null) {
			return null;
		}

		if (!$parentClass->hasConstant($constantName)) {
			return null;
		}

		$constant = $parentClass->getConstant($constantName);
		if ($constant->isPrivate()) {
			return null;
		}

		return $constant;
	}

}
