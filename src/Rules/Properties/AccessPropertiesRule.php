<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\PropertyFetch>
 */
class AccessPropertiesRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private bool $reportMagicProperties,
		private bool $checkDynamicProperties,
	)
	{
	}

	public function getNodeType(): string
	{
		return PropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->name instanceof Identifier) {
			$names = [$node->name->name];
		} else {
			$names = array_map(static fn (ConstantStringType $type): string => $type->getValue(), TypeUtils::getConstantStrings($scope->getType($node->name)));
		}

		$errors = [];
		foreach ($names as $name) {
			$errors = array_merge($errors, $this->processSingleProperty($scope, $node, $name));
		}

		return $errors;
	}

	/**
	 * @return RuleError[]
	 */
	private function processSingleProperty(Scope $scope, PropertyFetch $node, string $name): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->var),
			sprintf('Access to property $%s on an unknown class %%s.', SprintfHelper::escapeFormatString($name)),
			static fn (Type $type): bool => $type->canAccessProperties()->yes() && $type->hasProperty($name)->yes(),
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		if ($scope->isInExpressionAssign($node)) {
			return [];
		}

		if ($type->canAccessProperties()->no() || $type->canAccessProperties()->maybe() && !$scope->isUndefinedExpressionAllowed($node)) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot access property $%s on %s.',
					$name,
					$type->describe(VerbosityLevel::typeOnly()),
				))->build(),
			];
		}

		$has = $type->hasProperty($name);
		if (!$has->no() && $this->canAccessUndefinedProperties($scope, $node)) {
			return [];
		}

		if (!$has->yes()) {
			if ($scope->hasExpressionType($node)->yes()) {
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
				while ($parentClassReflection !== null) {
					if ($parentClassReflection->hasProperty($name)) {
						return [
							RuleErrorBuilder::message(sprintf(
								'Access to private property $%s of parent class %s.',
								$name,
								$parentClassReflection->getDisplayName(),
							))->build(),
						];
					}

					$parentClassReflection = $parentClassReflection->getParentClass();
				}
			}

			$ruleErrorBuilder = RuleErrorBuilder::message(sprintf(
				'Access to an undefined property %s::$%s.',
				$type->describe(VerbosityLevel::typeOnly()),
				$name,
			));
			if ($typeResult->getTip() !== null) {
				$ruleErrorBuilder->tip($typeResult->getTip());
			}

			return [
				$ruleErrorBuilder->build(),
			];
		}

		$propertyReflection = $type->getProperty($name, $scope);
		if (!$scope->canAccessProperty($propertyReflection)) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Access to %s property %s::$%s.',
					$propertyReflection->isPrivate() ? 'private' : 'protected',
					$type->describe(VerbosityLevel::typeOnly()),
					$name,
				))->build(),
			];
		}

		return [];
	}

	private function canAccessUndefinedProperties(Scope $scope, Node\Expr $node): bool
	{
		return $scope->isUndefinedExpressionAllowed($node) && !$this->checkDynamicProperties;
	}

}
