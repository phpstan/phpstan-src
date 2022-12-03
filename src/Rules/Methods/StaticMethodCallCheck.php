<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use DOMDocument;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function in_array;
use function sprintf;
use function strtolower;

class StaticMethodCallCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		private bool $checkFunctionNameCase,
		private bool $reportMagicMethods,
	)
	{
	}

	/**
	 * @param Name|Expr $class
	 * @return array{RuleError[], MethodReflection|null}
	 */
	public function check(
		Scope $scope,
		string $methodName,
		$class,
	): array
	{
		$errors = [];
		$isAbstract = false;
		if ($class instanceof Name) {
			$classStringType = $scope->getType(new Expr\ClassConstFetch($class, 'class'));
			if ($classStringType->hasMethod($methodName)->yes()) {
				return [[], null];
			}

			$className = (string) $class;
			$lowercasedClassName = strtolower($className);
			if (in_array($lowercasedClassName, ['self', 'static'], true)) {
				if (!$scope->isInClass()) {
					return [
						[
							RuleErrorBuilder::message(sprintf(
								'Calling %s::%s() outside of class scope.',
								$className,
								$methodName,
							))->build(),
						],
						null,
					];
				}
				$classType = $scope->resolveTypeByName($class);
			} elseif ($lowercasedClassName === 'parent') {
				if (!$scope->isInClass()) {
					return [
						[
							RuleErrorBuilder::message(sprintf(
								'Calling %s::%s() outside of class scope.',
								$className,
								$methodName,
							))->build(),
						],
						null,
					];
				}
				$currentClassReflection = $scope->getClassReflection();
				if ($currentClassReflection->getParentClass() === null) {
					return [
						[
							RuleErrorBuilder::message(sprintf(
								'%s::%s() calls parent::%s() but %s does not extend any class.',
								$scope->getClassReflection()->getDisplayName(),
								$scope->getFunctionName(),
								$methodName,
								$scope->getClassReflection()->getDisplayName(),
							))->build(),
						],
						null,
					];
				}

				if ($scope->getFunctionName() === null) {
					throw new ShouldNotHappenException();
				}

				$classType = $scope->resolveTypeByName($class);
			} else {
				if (!$this->reflectionProvider->hasClass($className)) {
					if ($scope->isInClassExists($className)) {
						return [[], null];
					}

					return [
						[
							RuleErrorBuilder::message(sprintf(
								'Call to static method %s() on an unknown class %s.',
								$methodName,
								$className,
							))->discoveringSymbolsTip()->build(),
						],
						null,
					];
				} else {
					$errors = $this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($className, $class)]);
				}

				$classType = $scope->resolveTypeByName($class);
			}

			$classReflection = $classType->getClassReflection();
			if ($classReflection !== null && $classReflection->hasNativeMethod($methodName) && $lowercasedClassName !== 'static') {
				$nativeMethodReflection = $classReflection->getNativeMethod($methodName);
				if ($nativeMethodReflection instanceof PhpMethodReflection || $nativeMethodReflection instanceof NativeMethodReflection) {
					$isAbstract = $nativeMethodReflection->isAbstract();
				}
			}
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $class),
				sprintf('Call to static method %s() on an unknown class %%s.', SprintfHelper::escapeFormatString($methodName)),
				static fn (Type $type): bool => $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes(),
			);
			$classType = $classTypeResult->getType();
			if ($classType instanceof ErrorType) {
				return [$classTypeResult->getUnknownClassErrors(), null];
			}
		}

		if ($classType instanceof GenericClassStringType) {
			$classType = $classType->getGenericType();
			if (!(new ObjectWithoutClassType())->isSuperTypeOf($classType)->yes()) {
				return [[], null];
			}
		} elseif ($classType->isString()->yes()) {
			return [[], null];
		}

		$typeForDescribe = $classType;
		if ($classType instanceof ThisType) {
			$typeForDescribe = $classType->getStaticObjectType();
		}
		$classType = TypeCombinator::remove($classType, new StringType());

		if (!$classType->canCallMethods()->yes()) {
			return [
				array_merge($errors, [
					RuleErrorBuilder::message(sprintf(
						'Cannot call static method %s() on %s.',
						$methodName,
						$typeForDescribe->describe(VerbosityLevel::typeOnly()),
					))->build(),
				]),
				null,
			];
		}

		if (!$classType->hasMethod($methodName)->yes()) {
			if (!$this->reportMagicMethods) {
				$directClassNames = TypeUtils::getDirectClassNames($classType);
				foreach ($directClassNames as $className) {
					if (!$this->reflectionProvider->hasClass($className)) {
						continue;
					}

					$classReflection = $this->reflectionProvider->getClass($className);
					if ($classReflection->hasNativeMethod('__callStatic')) {
						return [[], null];
					}
				}
			}

			return [
				array_merge($errors, [
					RuleErrorBuilder::message(sprintf(
						'Call to an undefined static method %s::%s().',
						$typeForDescribe->describe(VerbosityLevel::typeOnly()),
						$methodName,
					))->build(),
				]),
				null,
			];
		}

		$method = $classType->getMethod($methodName, $scope);
		if (!$method->isStatic()) {
			$function = $scope->getFunction();
			if (
				!$function instanceof MethodReflection
				|| $function->isStatic()
				|| !$scope->isInClass()
				|| (
					$classType instanceof TypeWithClassName
					&& $scope->getClassReflection()->getName() !== $classType->getClassName()
					&& !$scope->getClassReflection()->isSubclassOf($classType->getClassName())
				)
			) {
				// per php-src docs, this method can be called statically, even if declared non-static
				if (strtolower($method->getName()) === 'loadhtml' && $method->getDeclaringClass()->getName() === DOMDocument::class) {
					return [[], null];
				}

				return [
					array_merge($errors, [
						RuleErrorBuilder::message(sprintf(
							'Static call to instance method %s::%s().',
							$method->getDeclaringClass()->getDisplayName(),
							$method->getName(),
						))->build(),
					]),
					$method,
				];
			}
		}

		if (!$scope->canCallMethod($method)) {
			$errors = array_merge($errors, [
				RuleErrorBuilder::message(sprintf(
					'Call to %s %s %s() of class %s.',
					$method->isPrivate() ? 'private' : 'protected',
					$method->isStatic() ? 'static method' : 'method',
					$method->getName(),
					$method->getDeclaringClass()->getDisplayName(),
				))->build(),
			]);
		}

		if ($isAbstract) {
			return [
				[
					RuleErrorBuilder::message(sprintf(
						'Cannot call abstract%s method %s::%s().',
						$method->isStatic() ? ' static' : '',
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
					))->build(),
				],
				$method,
			];
		}

		$lowercasedMethodName = SprintfHelper::escapeFormatString(sprintf(
			'%s %s',
			$method->isStatic() ? 'static method' : 'method',
			$method->getDeclaringClass()->getDisplayName() . '::' . $method->getName() . '()',
		));

		if (
			$this->checkFunctionNameCase
			&& $method->getName() !== $methodName
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Call to %s with incorrect case: %s',
				$lowercasedMethodName,
				$methodName,
			))->build();
		}

		return [$errors, $method];
	}

}
