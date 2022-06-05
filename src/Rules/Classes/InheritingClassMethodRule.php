<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Methods\MethodParameterComparisonHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function count;
use function sprintf;

/** @implements Rule<Node\Stmt\Class_> */
class InheritingClassMethodRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion, private ReflectionProvider $reflectionProvider, private MethodParameterComparisonHelper $methodParameterComparisonHelper)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Class_::class;
	}

	/**
	 * @param Node\Stmt\Class_  $node
	 *
	 * @return array|RuleError[]|string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$implements = $node->implements;
		$extends = $node->extends;

		if (count($implements) === 0 || $extends === null) {
			return [];
		}

		$currentClassName = (string) $node->namespacedName;
		if (! $this->reflectionProvider->hasClass($currentClassName)) {
			return [];
		}

		$currentClassReflection = $this->reflectionProvider->getClass($currentClassName);

		$extendedClassName = (string) $node->extends;
		if (! $this->reflectionProvider->hasClass($extendedClassName)) {
			return [];
		}

		$extendedClassReflection = $this->reflectionProvider->getClass($extendedClassName);

		$messages = [];

		foreach ($currentClassReflection->getNativeReflection()->getMethods() as $method) {
			if ($extendedClassReflection->getName() !== $method->getDeclaringClass()->getName()) {
				continue;
			}

			$method = $currentClassReflection->getMethod($method->getName(), $scope);

			// Covered by other rules.
			if ($method->getDeclaringClass()->isTrait()) {
				continue;
			}

			foreach ($implements as $implement) {
				$interfaceClassName = (string) $implement;
				if (! $this->reflectionProvider->hasClass($interfaceClassName)) {
					continue;
				}

				$interface = $this->reflectionProvider->getClass($interfaceClassName);

				if (! $interface->hasMethod($method->getName())) {
					continue;
				}

				$interfaceMethod = $interface->getMethod($method->getName(), $scope);

				$interfaceMethodVariants = $interfaceMethod->getVariants();
				if (!$this->methodParameterComparisonHelper->isTypeCompatible($interfaceMethodVariants[0]->getReturnType(), $extendedClassReflection->getMethod($method->getName(), $scope)->getVariants()[0]->getReturnType(), $this->phpVersion->supportsReturnCovariance())) {
					if ($this->phpVersion->supportsReturnCovariance()) {
						$messages[] = RuleErrorBuilder::message(sprintf(
							'Return type %s of method %s::%s() is not covariant with return type %s of method %s::%s().',
							$extendedClassReflection->getMethod($method->getName(), $scope)->getVariants()[0]->getReturnType()->describe(VerbosityLevel::typeOnly()),
							$method->getDeclaringClass()->getName(),
							$method->getName(),
							$interfaceMethodVariants[0]->getReturnType()->describe(VerbosityLevel::typeOnly()),
							$interfaceMethod->getDeclaringClass()->getDisplayName(),
							$interfaceMethod->getName(),
						))->nonIgnorable()->build();
					} else {
						$messages[] = RuleErrorBuilder::message(sprintf(
							'Return type %s of method %s::%s() is not compatible with return type %s of method %s::%s().',
							$extendedClassReflection->getMethod($method->getName(), $scope)->getVariants()[0]->getReturnType()->describe(VerbosityLevel::typeOnly()),
							$method->getDeclaringClass()->getName(),
							$method->getName(),
							$interfaceMethodVariants[0]->getReturnType()->describe(VerbosityLevel::typeOnly()),
							$interfaceMethod->getDeclaringClass()->getDisplayName(),
							$interfaceMethod->getName(),
						))->nonIgnorable()->build();
					}
				}

				$messages = array_merge($messages, $this->methodParameterComparisonHelper->compare($this->getMethodPrototypeReflection($interfaceMethod, $interfaceMethod->getDeclaringClass()), $method));
			}
		}

		return $messages;
	}

	private function getMethodPrototypeReflection(MethodReflection $methodReflection, ClassReflection $classReflection): MethodPrototypeReflection
	{
		return new MethodPrototypeReflection(
			$methodReflection->getName(),
			$classReflection,
			$methodReflection->isStatic(),
			$methodReflection->isPrivate(),
			$methodReflection->isPublic(),
			false, // interface methods cant be abstract
			$methodReflection->isFinal()->yes(),
			$classReflection->getNativeMethod($methodReflection->getName())->getVariants(),
			null,
		);
	}

}
