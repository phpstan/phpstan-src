<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\PropertyFetch>
 */
class AccessPropertiesRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private bool $reportMagicProperties;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		RuleLevelHelper $ruleLevelHelper,
		bool $reportMagicProperties
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->reportMagicProperties = $reportMagicProperties;
	}

	public function getNodeType(): string
	{
		return PropertyFetch::class;
	}

	public function processNode(\PhpParser\Node $node, Scope $scope): array
	{
		if ($node->name instanceof \PhpParser\Node\Identifier) {
			$names = [$node->name->name];
		} else {
			$names = array_map(function (ConstantStringType $string): string {
				return $string->getValue();
			}, TypeUtils::getConstantStrings($scope->getType($node->name)));
		}

		$errors = [];
		foreach ($names as $name) {
			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->var,
				sprintf('Access to property $%s on an unknown class %%s.', $name),
				static function (Type $type) use ($name): bool {
					return $type->canAccessProperties()->yes() && $type->hasProperty($name)->yes();
				}
			);
			$type = $typeResult->getType();
			if ($type instanceof ErrorType) {
				$errors = array_merge($errors, $typeResult->getUnknownClassErrors());
				continue;
			}

			if ($scope->isInExpressionAssign($node)) {
				continue;
			}

			if (!$type->canAccessProperties()->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Cannot access property $%s on %s.',
					$name,
					$type->describe(VerbosityLevel::typeOnly())
				))->build();
				continue;
			}

			if (!$type->hasProperty($name)->yes()) {
				if ($scope->isSpecified($node)) {
					continue;
				}

				$classNames = $typeResult->getReferencedClasses();
				if (!$this->reportMagicProperties) {
					foreach ($classNames as $className) {
						if (!$this->reflectionProvider->hasClass($className)) {
							continue;
						}

						$classReflection = $this->reflectionProvider->getClass($className);
						if (
							$classReflection->hasNativeMethod('__get')
							|| $classReflection->hasNativeMethod('__set')
						) {
							continue;
						}
					}
				}

				if (count($classNames) === 1) {
					$referencedClass = $typeResult->getReferencedClasses()[0];
					$propertyClassReflection = $this->reflectionProvider->getClass($referencedClass);
					$parentClassReflection = $propertyClassReflection->getParentClass();
					while ($parentClassReflection !== false) {
						if ($parentClassReflection->hasProperty($name)) {
							$errors[] = RuleErrorBuilder::message(sprintf(
								'Access to private property $%s of parent class %s.',
								$name,
								$parentClassReflection->getDisplayName()
							))->build();
							continue;
						}

						$parentClassReflection = $parentClassReflection->getParentClass();
					}
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'Access to an undefined property %s::$%s.',
					$type->describe(VerbosityLevel::typeOnly()),
					$name
				))->build();
				continue;
			}

			$propertyReflection = $type->getProperty($name, $scope);
			if (!$scope->canAccessProperty($propertyReflection)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Access to %s property %s::$%s.',
					$propertyReflection->isPrivate() ? 'private' : 'protected',
					$type->describe(VerbosityLevel::typeOnly()),
					$name
				))->build();
				continue;
			}
		}

		return $errors;
	}

}
