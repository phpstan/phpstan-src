<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
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
		if ($node->name instanceof Identifier) {
			$names = [$node->name->name];
		} else {
			$names = array_map(static function (ConstantStringType $type): string {
				return $type->getValue();
			}, TypeUtils::getConstantStrings($scope->getType($node->name)));
		}

		$errors = [];
		foreach ($names as $name) {
			$errors = array_merge($errors, $this->processSingleProperty($scope, $node, $name));
		}

		return $errors;
	}

	/**
	 * @param Scope $scope
	 * @param PropertyFetch $node
	 * @param string $name
	 * @return RuleError[]
	 */
	private function processSingleProperty(Scope $scope, PropertyFetch $node, string $name): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->var,
			sprintf('Access to property $%s on an unknown class %%s.', SprintfHelper::escapeFormatString($name)),
			static function (Type $type) use ($name): bool {
				return $type->canAccessProperties()->yes() && $type->hasProperty($name)->yes();
			}
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		if ($scope->isInExpressionAssign($node)) {
			return [];
		}

		if (!$type->canAccessProperties()->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot access property $%s on %s.',
					$name,
					$type->describe(VerbosityLevel::typeOnly())
				))->build(),
			];
		}

		if (!$type->hasProperty($name)->yes()) {
			if ($scope->isSpecified($node)) {
				return [];
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
						return [];
					}
				}
			}

			if (count($classNames) === 1) {
				$referencedClass = $typeResult->getReferencedClasses()[0];
				$propertyClassReflection = $this->reflectionProvider->getClass($referencedClass);
				$parentClassReflection = $propertyClassReflection->getParentClass();
				while ($parentClassReflection !== false) {
					if ($parentClassReflection->hasProperty($name)) {
						return [
							RuleErrorBuilder::message(sprintf(
								'Access to private property $%s of parent class %s.',
								$name,
								$parentClassReflection->getDisplayName()
							))->build(),
						];
					}

					$parentClassReflection = $parentClassReflection->getParentClass();
				}
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Access to an undefined property %s::$%s.',
					$type->describe(VerbosityLevel::typeOnly()),
					$name
				))->build(),
			];
		}

		$propertyReflection = $type->getProperty($name, $scope);
		if (!$scope->canAccessProperty($propertyReflection)) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Access to %s property %s::$%s.',
					$propertyReflection->isPrivate() ? 'private' : 'protected',
					$type->describe(VerbosityLevel::typeOnly()),
					$name
				))->build(),
			];
		}

		return [];
	}

}
