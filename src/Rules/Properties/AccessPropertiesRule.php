<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
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
			$names = array_map(static fn (ConstantStringType $type): string => $type->getValue(), $scope->getType($node->name)->getConstantStrings());
		}

		$errors = [];
		foreach ($names as $name) {
			$errors = array_merge($errors, $this->processSingleProperty($scope, $node, $name));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
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

		$typeForDescribe = $type;
		if ($type instanceof StaticType) {
			$typeForDescribe = $type->getStaticObjectType();
		}

		if ($type->canAccessProperties()->no() || $type->canAccessProperties()->maybe() && !$scope->isUndefinedExpressionAllowed($node)) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot access property $%s on %s.',
					$name,
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
				))->identifier('property.nonObject')->build(),
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

			$classNames = $type->getObjectClassNames();
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
				$propertyClassReflection = $this->reflectionProvider->getClass($classNames[0]);
				$parentClassReflection = $propertyClassReflection->getParentClass();
				while ($parentClassReflection !== null) {
					if ($parentClassReflection->hasProperty($name)) {
						if ($scope->canAccessProperty($parentClassReflection->getProperty($name, $scope))) {
							return [];
						}
						return [
							RuleErrorBuilder::message(sprintf(
								'Access to private property $%s of parent class %s.',
								$name,
								$parentClassReflection->getDisplayName(),
							))->identifier('property.private')->build(),
						];
					}

					$parentClassReflection = $parentClassReflection->getParentClass();
				}
			}

			$ruleErrorBuilder = RuleErrorBuilder::message(sprintf(
				'Access to an undefined property %s::$%s.',
				$typeForDescribe->describe(VerbosityLevel::typeOnly()),
				$name,
			))->identifier('property.notFound');
			if ($typeResult->getTip() !== null) {
				$ruleErrorBuilder->tip($typeResult->getTip());
			} else {
				$ruleErrorBuilder->tip('Learn more: <fg=cyan>https://phpstan.org/blog/solving-phpstan-access-to-undefined-property</>');
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
				))->identifier(sprintf('property.%s', $propertyReflection->isPrivate() ? 'private' : 'protected'))->build(),
			];
		}

		return [];
	}

	private function canAccessUndefinedProperties(Scope $scope, Node\Expr $node): bool
	{
		return $scope->isUndefinedExpressionAllowed($node) && !$this->checkDynamicProperties;
	}

}
