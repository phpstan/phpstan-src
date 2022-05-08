<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use Traversable;
use function array_key_exists;
use function array_slice;
use function count;
use function sprintf;

class MethodParameterComparisonHelper
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	/**
	 * @return RuleError[]
	 */
	public function compare(MethodPrototypeReflection $prototype, PhpMethodFromParserNodeReflection $method): array
	{
		/** @var RuleError[] $messages */
		$messages = [];
		$prototypeVariant = $prototype->getVariants()[0];

		$methodVariant = ParametersAcceptorSelector::selectSingle($method->getVariants());
		$methodParameters = $methodVariant->getParameters();

		$prototypeAfterVariadic = false;
		foreach ($prototypeVariant->getParameters() as $i => $prototypeParameter) {
			if (!array_key_exists($i, $methodParameters)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Method %s::%s() overrides method %s::%s() but misses parameter #%d $%s.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$prototype->getName(),
					$i + 1,
					$prototypeParameter->getName(),
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
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
						$i + 1,
						$prototypeParameter->getName(),
						$prototype->getDeclaringClass()->getDisplayName(),
						$prototype->getName(),
					))->nonIgnorable()->build();
				}
			} elseif ($methodParameter->passedByReference()->no()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is not passed by reference but parameter #%d $%s of method %s::%s() is passed by reference.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$prototype->getName(),
				))->nonIgnorable()->build();
			}

			if ($prototypeParameter->isVariadic()) {
				$prototypeAfterVariadic = true;
				if (!$methodParameter->isVariadic()) {
					if (!$methodParameter->isOptional()) {
						if (count($methodParameters) !== $i + 1) {
							$messages[] = RuleErrorBuilder::message(sprintf(
								'Parameter #%d $%s of method %s::%s() is not optional.',
								$i + 1,
								$methodParameter->getName(),
								$method->getDeclaringClass()->getDisplayName(),
								$method->getName(),
							))->nonIgnorable()->build();
							continue;
						}

						$messages[] = RuleErrorBuilder::message(sprintf(
							'Parameter #%d $%s of method %s::%s() is not variadic but parameter #%d $%s of method %s::%s() is variadic.',
							$i + 1,
							$methodParameter->getName(),
							$method->getDeclaringClass()->getDisplayName(),
							$method->getName(),
							$i + 1,
							$prototypeParameter->getName(),
							$prototype->getDeclaringClass()->getDisplayName(),
							$prototype->getName(),
						))->nonIgnorable()->build();
						continue;
					} elseif (count($methodParameters) === $i + 1) {
						$messages[] = RuleErrorBuilder::message(sprintf(
							'Parameter #%d $%s of method %s::%s() is not variadic.',
							$i + 1,
							$methodParameter->getName(),
							$method->getDeclaringClass()->getDisplayName(),
							$method->getName(),
						))->nonIgnorable()->build();
					}
				}
			} elseif ($methodParameter->isVariadic()) {
				if ($this->phpVersion->supportsLessOverridenParametersWithVariadic()) {
					$remainingPrototypeParameters = array_slice($prototypeVariant->getParameters(), $i);
					foreach ($remainingPrototypeParameters as $j => $remainingPrototypeParameter) {
						if (!$remainingPrototypeParameter instanceof ParameterReflectionWithPhpDocs) {
							continue;
						}
						if ($methodParameter->getNativeType()->isSuperTypeOf($remainingPrototypeParameter->getNativeType())->yes()) {
							continue;
						}

						$messages[] = RuleErrorBuilder::message(sprintf(
							'Parameter #%d ...$%s (%s) of method %s::%s() is not contravariant with parameter #%d $%s (%s) of method %s::%s().',
							$i + 1,
							$methodParameter->getName(),
							$methodParameter->getNativeType()->describe(VerbosityLevel::typeOnly()),
							$method->getDeclaringClass()->getDisplayName(),
							$method->getName(),
							$i + $j + 1,
							$remainingPrototypeParameter->getName(),
							$remainingPrototypeParameter->getNativeType()->describe(VerbosityLevel::typeOnly()),
							$prototype->getDeclaringClass()->getDisplayName(),
							$prototype->getName(),
						))->nonIgnorable()->build();
					}
					break;
				}
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is variadic but parameter #%d $%s of method %s::%s() is not variadic.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$prototype->getName(),
				))->nonIgnorable()->build();
				continue;
			}

			if ($prototypeParameter->isOptional() && !$methodParameter->isOptional()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is required but parameter #%d $%s of method %s::%s() is optional.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getDisplayName(),
					$prototype->getName(),
				))->nonIgnorable()->build();
			}

			$methodParameterType = $methodParameter->getNativeType();

			if (!$prototypeParameter instanceof ParameterReflectionWithPhpDocs) {
				continue;
			}

			$prototypeParameterType = $prototypeParameter->getNativeType();
			if (!$this->phpVersion->supportsParameterTypeWidening()) {
				if (!$methodParameterType->equals($prototypeParameterType)) {
					$messages[] = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s (%s) of method %s::%s() does not match parameter #%d $%s (%s) of method %s::%s().',
						$i + 1,
						$methodParameter->getName(),
						$methodParameterType->describe(VerbosityLevel::typeOnly()),
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
						$i + 1,
						$prototypeParameter->getName(),
						$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
						$prototype->getDeclaringClass()->getDisplayName(),
						$prototype->getName(),
					))->nonIgnorable()->build();
				}
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
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getDisplayName(),
					$prototype->getName(),
				))->nonIgnorable()->build();
			} else {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of method %s::%s() is not compatible with parameter #%d $%s (%s) of method %s::%s().',
					$i + 1,
					$methodParameter->getName(),
					$methodParameterType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getDisplayName(),
					$prototype->getName(),
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

			if (
				$j === count($methodParameters) - 1
				&& $prototypeAfterVariadic
				&& !$methodParameter->isVariadic()
			) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is not variadic.',
					$j + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
				))->nonIgnorable()->build();
				continue;
			}

			if (!$methodParameter->isOptional()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is not optional.',
					$j + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
				))->nonIgnorable()->build();
				continue;
			}
		}

		return $messages;
	}

	public function isTypeCompatible(Type $methodParameterType, Type $prototypeParameterType, bool $supportsContravariance): bool
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
				if ($prototypeParameterType instanceof ObjectType && $prototypeParameterType->getClassName() === Traversable::class) {
					return true;
				}
			}

			return false;
		}

		return $methodParameterType->isSuperTypeOf($prototypeParameterType)->yes();
	}

}
