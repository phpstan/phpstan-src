<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\StaticCall>
 */
class CallStaticMethodsRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $reflectionProvider;

	/** @var \PHPStan\Rules\FunctionCallParametersCheck */
	private $check;

	/** @var \PHPStan\Rules\RuleLevelHelper */
	private $ruleLevelHelper;

	/** @var \PHPStan\Rules\ClassCaseSensitivityCheck */
	private $classCaseSensitivityCheck;

	/** @var bool */
	private $checkFunctionNameCase;

	/** @var bool */
	private $reportMagicMethods;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		FunctionCallParametersCheck $check,
		RuleLevelHelper $ruleLevelHelper,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		bool $checkFunctionNameCase,
		bool $reportMagicMethods
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->check = $check;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->checkFunctionNameCase = $checkFunctionNameCase;
		$this->reportMagicMethods = $reportMagicMethods;
	}

	public function getNodeType(): string
	{
		return StaticCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $node->name->name;

		$class = $node->class;
		$errors = [];
		$isAbstract = false;
		if ($class instanceof Name) {
			$className = (string) $class;
			$lowercasedClassName = strtolower($className);
			if (in_array($lowercasedClassName, ['self', 'static'], true)) {
				if (!$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Calling %s::%s() outside of class scope.',
							$className,
							$methodName
						))->build(),
					];
				}
				$classReflection = $scope->getClassReflection();
			} elseif ($lowercasedClassName === 'parent') {
				if (!$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Calling %s::%s() outside of class scope.',
							$className,
							$methodName
						))->build(),
					];
				}
				$currentClassReflection = $scope->getClassReflection();
				if ($currentClassReflection->getParentClass() === false) {
					return [
						RuleErrorBuilder::message(sprintf(
							'%s::%s() calls parent::%s() but %s does not extend any class.',
							$scope->getClassReflection()->getDisplayName(),
							$scope->getFunctionName(),
							$methodName,
							$scope->getClassReflection()->getDisplayName()
						))->build(),
					];
				}

				if ($scope->getFunctionName() === null) {
					throw new \PHPStan\ShouldNotHappenException();
				}

				$classReflection = $currentClassReflection->getParentClass();
			} else {
				if (!$this->reflectionProvider->hasClass($className)) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Call to static method %s() on an unknown class %s.',
							$methodName,
							$className
						))->build(),
					];
				} else {
					$errors = $this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($className, $class)]);
				}

				$classReflection = $this->reflectionProvider->getClass($className);
			}

			$className = $classReflection->getName();
			$classType = new ObjectType($className);

			if ($classReflection->hasNativeMethod($methodName) && $lowercasedClassName !== 'static') {
				$nativeMethodReflection = $classReflection->getNativeMethod($methodName);
				if ($nativeMethodReflection instanceof PhpMethodReflection || $nativeMethodReflection instanceof NativeMethodReflection) {
					$isAbstract = $nativeMethodReflection->isAbstract();
				}
			}
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$class,
				sprintf('Call to static method %s() on an unknown class %%s.', $methodName),
				static function (Type $type) use ($methodName): bool {
					return $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes();
				}
			);
			$classType = $classTypeResult->getType();
			if ($classType instanceof ErrorType) {
				return $classTypeResult->getUnknownClassErrors();
			}
		}

		if ($classType instanceof GenericClassStringType) {
			$classType = $classType->getGenericType();
		} elseif ((new StringType())->isSuperTypeOf($classType)->yes()) {
			return [];
		}

		$typeForDescribe = $classType;
		$classType = TypeCombinator::remove($classType, new StringType());

		if (!$classType->canCallMethods()->yes()) {
			return array_merge($errors, [
				RuleErrorBuilder::message(sprintf(
					'Cannot call static method %s() on %s.',
					$methodName,
					$typeForDescribe->describe(VerbosityLevel::typeOnly())
				))->build(),
			]);
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
						return [];
					}
				}
			}

			return array_merge($errors, [
				RuleErrorBuilder::message(sprintf(
					'Call to an undefined static method %s::%s().',
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
					$methodName
				))->build(),
			]);
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
				return array_merge($errors, [
					RuleErrorBuilder::message(sprintf(
						'Static call to instance method %s::%s().',
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName()
					))->build(),
				]);
			}
		}

		if (!$scope->canCallMethod($method)) {
			$errors = array_merge($errors, [
				RuleErrorBuilder::message(sprintf(
					'Call to %s %s %s() of class %s.',
					$method->isPrivate() ? 'private' : 'protected',
					$method->isStatic() ? 'static method' : 'method',
					$method->getName(),
					$method->getDeclaringClass()->getDisplayName()
				))->build(),
			]);
		}

		if ($isAbstract) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot call abstract%s method %s::%s().',
					$method->isStatic() ? ' static' : '',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName()
				))->build(),
			];
		}

		$lowercasedMethodName = sprintf(
			'%s %s',
			$method->isStatic() ? 'static method' : 'method',
			$method->getDeclaringClass()->getDisplayName() . '::' . $method->getName() . '()'
		);
		$displayMethodName = sprintf(
			'%s %s',
			$method->isStatic() ? 'Static method' : 'Method',
			$method->getDeclaringClass()->getDisplayName() . '::' . $method->getName() . '()'
		);

		$errors = array_merge($errors, $this->check->check(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->args,
				$method->getVariants()
			),
			$scope,
			$node,
			[
				$displayMethodName . ' invoked with %d parameter, %d required.',
				$displayMethodName . ' invoked with %d parameters, %d required.',
				$displayMethodName . ' invoked with %d parameter, at least %d required.',
				$displayMethodName . ' invoked with %d parameters, at least %d required.',
				$displayMethodName . ' invoked with %d parameter, %d-%d required.',
				$displayMethodName . ' invoked with %d parameters, %d-%d required.',
				'Parameter #%d %s of ' . $lowercasedMethodName . ' expects %s but %s given.',
				'Result of ' . $lowercasedMethodName . ' (void) is used.',
				'Parameter #%d %s of ' . $lowercasedMethodName . ' is passed by reference, so it expects variables only.',
				'Unable to resolve the template type %s in call to method ' . $lowercasedMethodName,
			]
		));

		if (
			$this->checkFunctionNameCase
			&& $method->getName() !== $methodName
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Call to %s with incorrect case: %s',
				$lowercasedMethodName,
				$methodName
			))->build();
		}

		return $errors;
	}

}
