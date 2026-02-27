<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_merge;
use function count;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
#[RegisteredRule(level: 0)]
final class InheritedMethodCompatibilityRule implements Rule
{

	public function __construct(
		private PhpVersion $phpVersion,
		private MethodParameterComparisonHelper $methodParameterComparisonHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if ($classReflection->isInterface()) {
			return [];
		}
		if ($classReflection->isAbstract()) {
			return [];
		}

		$errors = [];

		try {
			$nativeReflection = $classReflection->getNativeReflection();
		} catch (IdentifierNotFound) {
			return [];
		}

		foreach ($classReflection->getImmediateInterfaces() as $interface) {
			try {
				$interfaceNativeReflection = $interface->getNativeReflection();
			} catch (IdentifierNotFound) {
				continue;
			}

			foreach ($interfaceNativeReflection->getMethods() as $interfaceMethodReflection) {
				$methodName = $interfaceMethodReflection->getName();

				if (!$nativeReflection->hasMethod($methodName)) {
					continue;
				}

				$nativeMethod = $nativeReflection->getMethod($methodName);
				$declaringClassName = $nativeMethod->getDeclaringClass()->getName();

				// If the method is declared in the current class, OverridingMethodRule handles it
				if ($declaringClassName === $classReflection->getName()) {
					continue;
				}

				// If the method is declared in the interface itself, skip it
				if ($declaringClassName === $interface->getName()) {
					continue;
				}

				if (!$classReflection->hasNativeMethod($methodName)) {
					continue;
				}

				$inheritedMethod = $classReflection->getNativeMethod($methodName);
				$interfaceMethod = $interface->getNativeMethod($methodName);

				$errors = array_merge(
					$errors,
					$this->compareMethodSignatures(
						$interfaceMethod,
						$interface,
						$inheritedMethod,
					),
				);
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function compareMethodSignatures(
		ExtendedMethodReflection $interfaceMethod,
		ClassReflection $interfaceClass,
		ExtendedMethodReflection $inheritedMethod,
	): array
	{
		$interfaceVariants = $interfaceMethod->getVariants();
		$inheritedVariants = $inheritedMethod->getVariants();

		if (count($interfaceVariants) !== 1 || count($inheritedVariants) !== 1) {
			return [];
		}

		$interfaceVariant = $interfaceVariants[0];
		$inheritedVariant = $inheritedVariants[0];

		$messages = [];

		$interfaceParameters = $interfaceVariant->getParameters();
		$inheritedParameters = $inheritedVariant->getParameters();

		foreach ($interfaceParameters as $i => $interfaceParameter) {
			if (!array_key_exists($i, $inheritedParameters)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Method %s::%s() overrides method %s::%s() but misses parameter #%d $%s.',
					$inheritedMethod->getDeclaringClass()->getDisplayName(),
					$inheritedMethod->getName(),
					$interfaceClass->getDisplayName(true),
					$interfaceMethod->getName(),
					$i + 1,
					$interfaceParameter->getName(),
				))
					->nonIgnorable()
					->identifier('parameter.missing')
					->build();
				continue;
			}

			$inheritedParameter = $inheritedParameters[$i];

			// Check pass-by-reference compatibility
			if ($interfaceParameter->passedByReference()->no()) {
				if (!$inheritedParameter->passedByReference()->no()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s of method %s::%s() is passed by reference but parameter #%d $%s of method %s::%s() is not passed by reference.',
						$i + 1,
						$inheritedParameter->getName(),
						$inheritedMethod->getDeclaringClass()->getDisplayName(),
						$inheritedMethod->getName(),
						$i + 1,
						$interfaceParameter->getName(),
						$interfaceClass->getDisplayName(true),
						$interfaceMethod->getName(),
					))
						->nonIgnorable()
						->identifier('parameter.byRef')
						->build();
				}
			} elseif ($inheritedParameter->passedByReference()->no()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is not passed by reference but parameter #%d $%s of method %s::%s() is passed by reference.',
					$i + 1,
					$inheritedParameter->getName(),
					$inheritedMethod->getDeclaringClass()->getDisplayName(),
					$inheritedMethod->getName(),
					$i + 1,
					$interfaceParameter->getName(),
					$interfaceClass->getDisplayName(true),
					$interfaceMethod->getName(),
				))
					->nonIgnorable()
					->identifier('parameter.notByRef')
					->build();
			}

			if (!$interfaceVariant instanceof ExtendedFunctionVariant || !$inheritedVariant instanceof ExtendedFunctionVariant) {
				continue;
			}

			// Check parameter type compatibility
			$inheritedParameterType = $inheritedParameter->getNativeType();
			$interfaceParameterType = $interfaceParameter->getNativeType();

			if ($this->phpVersion->supportsParameterContravariance()) {
				if (
					!$inheritedParameterType instanceof MixedType
					&& !$inheritedParameterType->isSuperTypeOf($interfaceParameterType)->yes()
				) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s (%s) of method %s::%s() is not contravariant with parameter #%d $%s (%s) of method %s::%s().',
						$i + 1,
						$inheritedParameter->getName(),
						$inheritedParameterType->describe(VerbosityLevel::typeOnly()),
						$inheritedMethod->getDeclaringClass()->getDisplayName(),
						$inheritedMethod->getName(),
						$i + 1,
						$interfaceParameter->getName(),
						$interfaceParameterType->describe(VerbosityLevel::typeOnly()),
						$interfaceClass->getDisplayName(true),
						$interfaceMethod->getName(),
					))
						->nonIgnorable()
						->identifier('method.childParameterType')
						->build();
				}
			} elseif (!$inheritedParameterType instanceof MixedType && !$inheritedParameterType->equals($interfaceParameterType)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of method %s::%s() is not compatible with parameter #%d $%s (%s) of method %s::%s().',
					$i + 1,
					$inheritedParameter->getName(),
					$inheritedParameterType->describe(VerbosityLevel::typeOnly()),
					$inheritedMethod->getDeclaringClass()->getDisplayName(),
					$inheritedMethod->getName(),
					$i + 1,
					$interfaceParameter->getName(),
					$interfaceParameterType->describe(VerbosityLevel::typeOnly()),
					$interfaceClass->getDisplayName(true),
					$interfaceMethod->getName(),
				))
					->nonIgnorable()
					->identifier('method.childParameterType')
					->build();
			}
		}

		if ($interfaceVariant instanceof ExtendedFunctionVariant && $inheritedVariant instanceof ExtendedFunctionVariant) {
			$inheritedReturnType = $inheritedVariant->getNativeReturnType();
			$interfaceReturnType = $interfaceVariant->getNativeReturnType();

			if (!$this->methodParameterComparisonHelper->isReturnTypeCompatible($interfaceReturnType, $inheritedReturnType, $this->phpVersion->supportsReturnCovariance())) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not covariant with return type %s of method %s::%s().',
					$inheritedReturnType->describe(VerbosityLevel::typeOnly()),
					$inheritedMethod->getDeclaringClass()->getDisplayName(),
					$inheritedMethod->getName(),
					$interfaceReturnType->describe(VerbosityLevel::typeOnly()),
					$interfaceClass->getDisplayName(true),
					$interfaceMethod->getName(),
				))
					->nonIgnorable()
					->identifier('method.childReturnType')
					->build();
			}
		}

		return $messages;
	}

}
