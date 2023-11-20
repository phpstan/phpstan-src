<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\StaticPropertyFetch>
 */
class AccessStaticPropertiesRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return StaticPropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->name instanceof Node\VarLikeIdentifier) {
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
	private function processSingleProperty(Scope $scope, StaticPropertyFetch $node, string $name): array
	{
		$messages = [];
		if ($node->class instanceof Name) {
			$class = (string) $node->class;
			$lowercasedClass = strtolower($class);
			if (in_array($lowercasedClass, ['self', 'static'], true)) {
				if (!$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Accessing %s::$%s outside of class scope.',
							$class,
							$name,
						))->identifier(sprintf('outOfClass.%s', $lowercasedClass))->build(),
					];
				}
				$classType = $scope->resolveTypeByName($node->class);
			} elseif ($lowercasedClass === 'parent') {
				if (!$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Accessing %s::$%s outside of class scope.',
							$class,
							$name,
						))->identifier('outOfClass.parent')->build(),
					];
				}
				if ($scope->getClassReflection()->getParentClass() === null) {
					return [
						RuleErrorBuilder::message(sprintf(
							'%s::%s() accesses parent::$%s but %s does not extend any class.',
							$scope->getClassReflection()->getDisplayName(),
							$scope->getFunctionName(),
							$name,
							$scope->getClassReflection()->getDisplayName(),
						))->identifier('class.noParent')->build(),
					];
				}

				if ($scope->getFunctionName() === null) {
					throw new ShouldNotHappenException();
				}

				$currentMethodReflection = $scope->getClassReflection()->getNativeMethod($scope->getFunctionName());
				if (!$currentMethodReflection->isStatic()) {
					// calling parent::method() from instance method
					return [];
				}

				$classType = $scope->resolveTypeByName($node->class);
			} else {
				if (!$this->reflectionProvider->hasClass($class)) {
					if ($scope->isInClassExists($class)) {
						return [];
					}

					return [
						RuleErrorBuilder::message(sprintf(
							'Access to static property $%s on an unknown class %s.',
							$name,
							$class,
						))
							->discoveringSymbolsTip()
							->identifier('class.notFound')
							->build(),
					];
				}

				$messages = $this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($class, $node->class)]);

				$classType = $scope->resolveTypeByName($node->class);
			}
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->class),
				sprintf('Access to static property $%s on an unknown class %%s.', SprintfHelper::escapeFormatString($name)),
				static fn (Type $type): bool => $type->canAccessProperties()->yes() && $type->hasProperty($name)->yes(),
			);
			$classType = $classTypeResult->getType();
			if ($classType instanceof ErrorType) {
				return $classTypeResult->getUnknownClassErrors();
			}
		}

		if ($classType->isString()->yes()) {
			return [];
		}

		$typeForDescribe = $classType;
		if ($classType instanceof ThisType) {
			$typeForDescribe = $classType->getStaticObjectType();
		}
		$classType = TypeCombinator::remove($classType, new StringType());

		if ($scope->isInExpressionAssign($node)) {
			return [];
		}

		if ($classType->canAccessProperties()->no() || $classType->canAccessProperties()->maybe() && !$scope->isUndefinedExpressionAllowed($node)) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Cannot access static property $%s on %s.',
					$name,
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
				))->identifier('staticProperty.nonObject')->build(),
			]);
		}

		$has = $classType->hasProperty($name);
		if (!$has->no() && $scope->isUndefinedExpressionAllowed($node)) {
			return [];
		}

		if (!$has->yes()) {
			if ($scope->hasExpressionType($node)->yes()) {
				return $messages;
			}

			$classNames = $classType->getObjectClassNames();
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
								'Access to private static property $%s of parent class %s.',
								$name,
								$parentClassReflection->getDisplayName(),
							))->identifier('staticProperty.private')->build(),
						];
					}

					$parentClassReflection = $parentClassReflection->getParentClass();
				}
			}

			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to an undefined static property %s::$%s.',
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
					$name,
				))->identifier('staticProperty.notFound')->build(),
			]);
		}

		$property = $classType->getProperty($name, $scope);
		if (!$property->isStatic()) {
			$hasPropertyTypes = TypeUtils::getHasPropertyTypes($classType);
			foreach ($hasPropertyTypes as $hasPropertyType) {
				if ($hasPropertyType->getPropertyName() === $name) {
					return [];
				}
			}

			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Static access to instance property %s::$%s.',
					$property->getDeclaringClass()->getDisplayName(),
					$name,
				))->identifier('property.staticAccess')->build(),
			]);
		}

		if (!$scope->canAccessProperty($property)) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to %s property $%s of class %s.',
					$property->isPrivate() ? 'private' : 'protected',
					$name,
					$property->getDeclaringClass()->getDisplayName(),
				))->identifier(sprintf('staticProperty.%s', $property->isPrivate() ? 'private' : 'protected'))->build(),
			]);
		}

		return $messages;
	}

}
