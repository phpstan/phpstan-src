<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpParameterReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<InClassMethodNode>
 */
class OverridingMethodRule implements Rule
{

	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $scope->getFunction();
		if (!$method instanceof PhpMethodFromParserNodeReflection) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$prototype = $method->getPrototype();
		if ($prototype->getDeclaringClass()->getName() === $method->getDeclaringClass()->getName()) {
			return [];
		}
		if (!$prototype instanceof MethodPrototypeReflection) {
			return [];
		}

		$messages = [];
		if ($prototype->isFinal()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Method %s::%s() overrides final method %s::%s().',
				$method->getDeclaringClass()->getName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getName(),
				$prototype->getName()
			))->nonIgnorable()->build();
		}

		if ($prototype->isPublic()) {
			if (!$method->isPublic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s method %s::%s() overriding public method %s::%s() should also be public.',
					$method->isPrivate() ? 'Private' : 'Protected',
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}
		} elseif ($method->isPrivate()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Private method %s::%s() overriding protected method %s::%s() should be protected or public.',
				$method->getDeclaringClass()->getName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getName(),
				$prototype->getName()
			))->nonIgnorable()->build();
		}

		if ($prototype->isStatic()) {
			if (!$method->isStatic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Non-static method %s::%s() overrides static method %s::%s().',
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}
		} elseif ($method->isStatic()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Static method %s::%s() overrides non-static method %s::%s().',
				$method->getDeclaringClass()->getName(),
				$method->getName(),
				$prototype->getDeclaringClass()->getName(),
				$prototype->getName()
			))->nonIgnorable()->build();
		}

		if (strtolower($method->getName()) === '__construct') {
			if (!$prototype->isAbstract()) {
				return $messages;
			}
		}

		$prototypeVariants = $prototype->getVariants();
		if (count($prototypeVariants) !== 1) {
			return $messages;
		}

		$prototypeVariant = $prototypeVariants[0];

		$methodVariant = ParametersAcceptorSelector::selectSingle($method->getVariants());
		$methodParameters = $methodVariant->getParameters();

		foreach ($prototypeVariant->getParameters() as $i => $prototypeParameter) {
			if (!array_key_exists($i, $methodParameters)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Method %s::%s() overrides method %s::%s() but misses parameter #%d $%s.',
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName(),
					$i + 1,
					$prototypeParameter->getName()
				))->nonIgnorable()->build();
				continue;
			}

			$methodParameter = $methodParameters[$i];
			if ($prototypeParameter->passedByReference()->no()) {
				if (!$methodParameter->passedByReference()->no()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s of method %s::%s() is passed by reference but parameter #%d $%s of method %s::%s() is not passed by reference.',
						$i + 1,
						$methodParameter->getName(),
						$method->getDeclaringClass()->getName(),
						$method->getName(),
						$i + 1,
						$prototypeParameter->getName(),
						$prototype->getDeclaringClass()->getName(),
						$prototype->getName()
					))->nonIgnorable()->build();
				}
			} elseif ($methodParameter->passedByReference()->no()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is not passed by reference but parameter #%d $%s of method %s::%s() is passed by reference.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}

			if ($prototypeParameter->isVariadic()) {
				if (!$methodParameter->isVariadic()) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s of method %s::%s() is not variadic but parameter #%d $%s of method %s::%s() is variadic.',
						$i + 1,
						$methodParameter->getName(),
						$method->getDeclaringClass()->getName(),
						$method->getName(),
						$i + 1,
						$prototypeParameter->getName(),
						$prototype->getDeclaringClass()->getName(),
						$prototype->getName()
					))->nonIgnorable()->build();
					continue;
				}
			} elseif ($methodParameter->isVariadic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is variadic but parameter #%d $%s of method %s::%s() is not variadic.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
				continue;
			}

			if ($prototypeParameter->isOptional() && !$methodParameter->isOptional()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is required but parameter #%d $%s of method %s::%s() is optional.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}

			$methodParameterType = $methodParameter->getNativeType();

			if ($prototypeParameter instanceof PhpParameterReflection) {
				$prototypeParameterType = $prototypeParameter->getNativeType();
			} elseif ($prototypeParameter instanceof NativeParameterReflection) {
				$prototypeParameterType = $prototypeParameter->getType();
			} else {
				continue;
			}

			if ($this->isTypeCompatible($methodParameterType, $prototypeParameterType, $this->phpVersion->supportsParameterContravariance())) {
				continue;
			}

			if ($this->phpVersion->supportsParameterContravariance()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of method %s::%s() is not contravariant with parameter #%d $%s (%s) of method %s::%s().',
					$i + 1,
					$methodParameter->getName(),
					$methodParameterType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			} else {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of method %s::%s() is not compatible with parameter #%d $%s (%s) of method %s::%s().',
					$i + 1,
					$methodParameter->getName(),
					$methodParameterType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}
		}

		if (!isset($i)) {
			$i = -1;
		}

		foreach ($methodParameters as $j => $methodParameter) {
			if ($j <= $i) {
				continue;
			}

			if ($methodParameter->isOptional()) {
				continue;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Parameter #%d $%s of method %s::%s() is not optional.',
				$j + 1,
				$methodParameter->getName(),
				$method->getDeclaringClass()->getName(),
				$method->getName()
			))->nonIgnorable()->build();
		}

		$methodReturnType = $methodVariant->getNativeReturnType();

		if ($prototypeVariant instanceof FunctionVariantWithPhpDocs) {
			$prototypeReturnType = $prototypeVariant->getNativeReturnType();
		} else {
			$prototypeReturnType = $prototypeVariant->getReturnType();
		}

		if (!$this->isTypeCompatible($prototypeReturnType, $methodReturnType, $this->phpVersion->supportsReturnCovariance())) {
			if ($this->phpVersion->supportsReturnCovariance()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not covariant with return type %s of method %s::%s().',
					$methodReturnType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$prototypeReturnType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			} else {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Return type %s of method %s::%s() is not compatible with return type %s of method %s::%s().',
					$methodReturnType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getName(),
					$method->getName(),
					$prototypeReturnType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getName(),
					$prototype->getName()
				))->nonIgnorable()->build();
			}
		}

		return $messages;
	}

	private function isTypeCompatible(Type $methodParameterType, Type $prototypeParameterType, bool $supportsContravariance): bool
	{
		if ($methodParameterType instanceof MixedType) {
			return true;
		}

		if (!$supportsContravariance) {
			if (TypeCombinator::containsNull($methodParameterType)) {
				$prototypeParameterType = TypeCombinator::removeNull($prototypeParameterType);
			}
			$methodParameterType = TypeCombinator::removeNull($methodParameterType);
			if ($methodParameterType->equals($prototypeParameterType)) {
				return true;
			}

			if ($methodParameterType instanceof IterableType) {
				if ($prototypeParameterType instanceof ArrayType) {
					return true;
				}
				if ($prototypeParameterType instanceof ObjectType && $prototypeParameterType->getClassName() === \Traversable::class) {
					return true;
				}
			}

			return false;
		}

		return $methodParameterType->isSuperTypeOf($prototypeParameterType)->yes();
	}

}
