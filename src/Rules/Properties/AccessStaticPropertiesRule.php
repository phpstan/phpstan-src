<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\StaticPropertyFetch>
 */
class AccessStaticPropertiesRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		RuleLevelHelper $ruleLevelHelper,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
	}

	public function getNodeType(): string
	{
		return StaticPropertyFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node->name instanceof Node\VarLikeIdentifier
			&& !$node->name instanceof Node\Expr\Variable // Variable variable like static::${$foo}
		) {
			return [];
		}

		$name = $node->name->name;
		if ($name instanceof Node\Expr) {
			return [];
		}

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
							$name
						))->build(),
					];
				}
				$className = $scope->getClassReflection()->getName();
			} elseif ($lowercasedClass === 'parent') {
				if (!$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Accessing %s::$%s outside of class scope.',
							$class,
							$name
						))->build(),
					];
				}
				if ($scope->getClassReflection()->getParentClass() === false) {
					return [
						RuleErrorBuilder::message(sprintf(
							'%s::%s() accesses parent::$%s but %s does not extend any class.',
							$scope->getClassReflection()->getDisplayName(),
							$scope->getFunctionName(),
							$name,
							$scope->getClassReflection()->getDisplayName()
						))->build(),
					];
				}

				if ($scope->getFunctionName() === null) {
					throw new \PHPStan\ShouldNotHappenException();
				}

				$currentMethodReflection = $scope->getClassReflection()->getNativeMethod($scope->getFunctionName());
				if (!$currentMethodReflection->isStatic()) {
					// calling parent::method() from instance method
					return [];
				}

				$className = $scope->getClassReflection()->getParentClass()->getName();
			} else {
				if (!$this->reflectionProvider->hasClass($class)) {
					if ($scope->isInClassExists($class)) {
						return [];
					}

					return [
						RuleErrorBuilder::message(sprintf(
							'Access to static property $%s on an unknown class %s.',
							$name,
							$class
						))->discoveringSymbolsTip()->build(),
					];
				} else {
					$messages = $this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($class, $node->class)]);
				}

				$classReflection = $this->reflectionProvider->getClass($class);
				$className = $this->reflectionProvider->getClass($class)->getName();
				if ($classReflection->isTrait()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Access to static property $%s on trait %s.',
							$name,
							$className
						))->build(),
					];
				}
			}

			$classType = new ObjectType($className);
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->class,
				sprintf('Access to static property $%s on an unknown class %%s.', $name),
				static function (Type $type) use ($name): bool {
					return $type->canAccessProperties()->yes() && $type->hasProperty($name)->yes();
				}
			);
			$classType = $classTypeResult->getType();
			if ($classType instanceof ErrorType) {
				return $classTypeResult->getUnknownClassErrors();
			}
		}

		if ((new StringType())->isSuperTypeOf($classType)->yes()) {
			return [];
		}

		$typeForDescribe = $classType;
		$classType = TypeCombinator::remove($classType, new StringType());

		if ($scope->isInExpressionAssign($node)) {
			if (!$node->name instanceof Node\Expr) {
				return [];
			}

			$assignNames = array_map(static function (\PHPStan\Type\Constant\ConstantStringType $string): string {
				return $string->getValue();
			}, TypeUtils::getConstantStrings($scope->getType($node->name)));

			foreach ($assignNames as $assignName) {
				if ($classType->hasProperty($assignName)->yes()) {
					continue;
				}

				$messages[] = RuleErrorBuilder::message(sprintf(
					'Cannot access static property $%s on %s.',
					$assignName,
					$typeForDescribe->describe(VerbosityLevel::typeOnly())
				))->build();
			}

			return $messages;
		}

		if ($node->name instanceof Node\Expr\Variable) {
			return [];
		}

		if (!$classType->canAccessProperties()->yes()) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Cannot access static property $%s on %s.',
					$name,
					$typeForDescribe->describe(VerbosityLevel::typeOnly())
				))->build(),
			]);
		}

		if (!$classType->hasProperty($name)->yes()) {
			if ($scope->isSpecified($node)) {
				return $messages;
			}

			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to an undefined static property %s::$%s.',
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
					$name
				))->build(),
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
					$name
				))->build(),
			]);
		}

		if (!$scope->canAccessProperty($property)) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to %s property $%s of class %s.',
					$property->isPrivate() ? 'private' : 'protected',
					$name,
					$property->getDeclaringClass()->getDisplayName()
				))->build(),
			]);
		}

		return $messages;
	}

}
