<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<ClassPropertyNode>
 */
class OverridingPropertyRule implements Rule
{

	private bool $checkPhpDocMethodSignatures;

	public function __construct(bool $checkPhpDocMethodSignatures)
	{
		$this->checkPhpDocMethodSignatures = $checkPhpDocMethodSignatures;
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$classReflection = $scope->getClassReflection();
		$prototype = $this->findPrototype($classReflection, $node->getName());
		if ($prototype === null) {
			return [];
		}

		$errors = [];
		if ($prototype->isStatic()) {
			if (!$node->isStatic()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Non-static property %s::$%s overrides static property %s::$%s.',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName()
				))->nonIgnorable()->build();
			}
		} elseif ($node->isStatic()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Static property %s::$%s overrides non-static property %s::$%s.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName()
			))->nonIgnorable()->build();
		}

		if ($prototype->isReadOnly()) {
			if (!$node->isReadOnly()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Readwrite property %s::$%s overrides readonly property %s::$%s.',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName()
				))->nonIgnorable()->build();
			}
		} elseif ($node->isReadOnly()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Readonly property %s::$%s overrides readwrite property %s::$%s.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName()
			))->nonIgnorable()->build();
		}

		if ($prototype->isPublic()) {
			if (!$node->isPublic()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s property %s::$%s overriding public property %s::$%s should also be public.',
					$node->isPrivate() ? 'Private' : 'Protected',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName()
				))->nonIgnorable()->build();
			}
		} elseif ($node->isPrivate()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Private property %s::$%s overriding protected property %s::$%s should be protected or public.',
				$classReflection->getDisplayName(),
				$node->getName(),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName()
			))->nonIgnorable()->build();
		}

		$typeErrors = [];
		if ($prototype->hasNativeType()) {
			if ($node->getNativeType() === null) {
				$typeErrors[] = RuleErrorBuilder::message(sprintf(
					'Property %s::$%s overriding property %s::$%s (%s) should also have native type %s.',
					$classReflection->getDisplayName(),
					$node->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$node->getName(),
					$prototype->getNativeType()->describe(VerbosityLevel::typeOnly()),
					$prototype->getNativeType()->describe(VerbosityLevel::typeOnly())
				))->nonIgnorable()->build();
			} else {
				$nativeType = ParserNodeTypeToPHPStanType::resolve($node->getNativeType(), $scope->getClassReflection()->getName());
				if (!$prototype->getNativeType()->equals($nativeType)) {
					$typeErrors[] = RuleErrorBuilder::message(sprintf(
						'Type %s of property %s::$%s is not the same as type %s of overridden property %s::$%s.',
						$nativeType->describe(VerbosityLevel::typeOnly()),
						$classReflection->getDisplayName(),
						$node->getName(),
						$prototype->getNativeType()->describe(VerbosityLevel::typeOnly()),
						$prototype->getDeclaringClass()->getDisplayName(),
						$node->getName()
					))->nonIgnorable()->build();
				}
			}
		} elseif ($node->getNativeType() !== null) {
			$typeErrors[] = RuleErrorBuilder::message(sprintf(
				'Property %s::$%s (%s) overriding property %s::$%s should not have a native type.',
				$classReflection->getDisplayName(),
				$node->getName(),
				ParserNodeTypeToPHPStanType::resolve($node->getNativeType(), $scope->getClassReflection()->getName())->describe(VerbosityLevel::typeOnly()),
				$prototype->getDeclaringClass()->getDisplayName(),
				$node->getName()
			))->nonIgnorable()->build();
		}

		$errors = array_merge($errors, $typeErrors);

		if (!$this->checkPhpDocMethodSignatures) {
			return $errors;
		}

		if (count($typeErrors) > 0) {
			return $errors;
		}

		$propertyReflection = $classReflection->getNativeProperty($node->getName());
		if ($propertyReflection->getReadableType()->equals($prototype->getReadableType())) {
			return $errors;
		}

		$verbosity = VerbosityLevel::getRecommendedLevelByType($prototype->getReadableType(), $propertyReflection->getReadableType());

		$errors[] = RuleErrorBuilder::message(sprintf(
			'Type %s of property %s::$%s is not the same as type %s of overridden property %s::$%s.',
			$propertyReflection->getReadableType()->describe($verbosity),
			$classReflection->getDisplayName(),
			$node->getName(),
			$prototype->getReadableType()->describe($verbosity),
			$prototype->getDeclaringClass()->getDisplayName(),
			$node->getName()
		))->build();

		return $errors;
	}

	private function findPrototype(ClassReflection $classReflection, string $propertyName): ?PhpPropertyReflection
	{
		$parentClass = $classReflection->getParentClass();
		if ($parentClass === false) {
			return null;
		}

		if (!$parentClass->hasNativeProperty($propertyName)) {
			return null;
		}

		$property = $parentClass->getNativeProperty($propertyName);
		if ($property->isPrivate()) {
			return null;
		}

		return $property;
	}

}
