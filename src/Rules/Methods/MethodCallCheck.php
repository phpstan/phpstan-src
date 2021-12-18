<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node\Expr;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
use function strtolower;

class MethodCallCheck
{

	private ReflectionProvider $reflectionProvider;

	private RuleLevelHelper $ruleLevelHelper;

	private bool $checkFunctionNameCase;

	private bool $reportMagicMethods;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		RuleLevelHelper $ruleLevelHelper,
		bool $checkFunctionNameCase,
		bool $reportMagicMethods,
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->checkFunctionNameCase = $checkFunctionNameCase;
		$this->reportMagicMethods = $reportMagicMethods;
	}

	/**
	 * @return array{RuleError[], MethodReflection|null}
	 */
	public function check(
		Scope $scope,
		string $methodName,
		Expr $var,
	): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $var),
			sprintf('Call to method %s() on an unknown class %%s.', SprintfHelper::escapeFormatString($methodName)),
			static fn (Type $type): bool => $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes(),
		);

		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return [$typeResult->getUnknownClassErrors(), null];
		}
		if (!$type->canCallMethods()->yes()) {
			return [
				[
					RuleErrorBuilder::message(sprintf(
						'Cannot call method %s() on %s.',
						$methodName,
						$type->describe(VerbosityLevel::typeOnly()),
					))->build(),
				],
				null,
			];
		}

		if (!$type->hasMethod($methodName)->yes()) {
			$directClassNames = $typeResult->getReferencedClasses();
			if (!$this->reportMagicMethods) {
				foreach ($directClassNames as $className) {
					if (!$this->reflectionProvider->hasClass($className)) {
						continue;
					}

					$classReflection = $this->reflectionProvider->getClass($className);
					if ($classReflection->hasNativeMethod('__call')) {
						return [[], null];
					}
				}
			}

			if (count($directClassNames) === 1) {
				$referencedClass = $directClassNames[0];
				$methodClassReflection = $this->reflectionProvider->getClass($referencedClass);
				$parentClassReflection = $methodClassReflection->getParentClass();
				while ($parentClassReflection !== null) {
					if ($parentClassReflection->hasMethod($methodName)) {
						$methodReflection = $parentClassReflection->getMethod($methodName, $scope);
						return [
							[
								RuleErrorBuilder::message(sprintf(
									'Call to private method %s() of parent class %s.',
									$methodReflection->getName(),
									$parentClassReflection->getDisplayName(),
								))->build(),
							],
							$methodReflection,
						];
					}

					$parentClassReflection = $parentClassReflection->getParentClass();
				}
			}

			return [
				[
					RuleErrorBuilder::message(sprintf(
						'Call to an undefined method %s::%s().',
						$type->describe(VerbosityLevel::typeOnly()),
						$methodName,
					))->build(),
				],
				null,
			];
		}

		$methodReflection = $type->getMethod($methodName, $scope);
		$declaringClass = $methodReflection->getDeclaringClass();
		$messagesMethodName = SprintfHelper::escapeFormatString($declaringClass->getDisplayName() . '::' . $methodReflection->getName() . '()');
		$errors = [];
		if (!$scope->canCallMethod($methodReflection)) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Call to %s method %s() of class %s.',
				$methodReflection->isPrivate() ? 'private' : 'protected',
				$methodReflection->getName(),
				$declaringClass->getDisplayName(),
			))->build();
		}

		if (
			$this->checkFunctionNameCase
			&& strtolower($methodReflection->getName()) === strtolower($methodName)
			&& $methodReflection->getName() !== $methodName
		) {
			$errors[] = RuleErrorBuilder::message(
				sprintf('Call to method %s with incorrect case: %s', $messagesMethodName, $methodName),
			)->build();
		}

		return [$errors, $methodReflection];
	}

}
