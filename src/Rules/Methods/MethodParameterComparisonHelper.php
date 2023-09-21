<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
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

	public function __construct(private PhpVersion $phpVersion, private bool $genericPrototypeMessage)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function compare(MethodPrototypeReflection $prototype, PhpMethodFromParserNodeReflection $method, bool $ignorable = false): array
	{
		/** @var list<IdentifierRuleError> $messages */
		$messages = [];
		$prototypeVariant = $prototype->getVariants()[0];

		$methodVariant = ParametersAcceptorSelector::selectSingle($method->getVariants());
		$methodParameters = $methodVariant->getParameters();

		$prototypeAfterVariadic = false;
		foreach ($prototypeVariant->getParameters() as $i => $prototypeParameter) {
			if (!array_key_exists($i, $methodParameters)) {
				$error = RuleErrorBuilder::message(sprintf(
					'Method %s::%s() overrides method %s::%s() but misses parameter #%d $%s.',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
					$i + 1,
					$prototypeParameter->getName(),
				))->identifier('parameter.missing');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $error->build();

				continue;
			}

			$methodParameter = $methodParameters[$i];
			if ($prototypeParameter->passedByReference()->no()) {
				if (!$methodParameter->passedByReference()->no()) {
					$error = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s of method %s::%s() is passed by reference but parameter #%d $%s of method %s::%s() is not passed by reference.',
						$i + 1,
						$methodParameter->getName(),
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
						$i + 1,
						$prototypeParameter->getName(),
						$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
						$prototype->getName(),
					))->identifier('parameter.byRef');

					if (! $ignorable) {
						$error->nonIgnorable();
					}

					$messages[] = $error->build();
				}
			} elseif ($methodParameter->passedByReference()->no()) {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is not passed by reference but parameter #%d $%s of method %s::%s() is passed by reference.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
				))->identifier('parameter.notByRef');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $error->build();
			}

			if ($prototypeParameter->isVariadic()) {
				$prototypeAfterVariadic = true;
				if (!$methodParameter->isVariadic()) {
					if (!$methodParameter->isOptional()) {
						if (count($methodParameters) !== $i + 1) {
							$error = RuleErrorBuilder::message(sprintf(
								'Parameter #%d $%s of method %s::%s() is not optional.',
								$i + 1,
								$methodParameter->getName(),
								$method->getDeclaringClass()->getDisplayName(),
								$method->getName(),
							))->identifier('parameter.notOptional');

							if (! $ignorable) {
								$error->nonIgnorable();
							}

							$messages[] = $error->build();

							continue;
						}

						$error = RuleErrorBuilder::message(sprintf(
							'Parameter #%d $%s of method %s::%s() is not variadic but parameter #%d $%s of method %s::%s() is variadic.',
							$i + 1,
							$methodParameter->getName(),
							$method->getDeclaringClass()->getDisplayName(),
							$method->getName(),
							$i + 1,
							$prototypeParameter->getName(),
							$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
							$prototype->getName(),
						))->identifier('parameter.notVariadic');

						if (! $ignorable) {
							$error->nonIgnorable();
						}

						$messages[] = $error->build();

						continue;
					} elseif (count($methodParameters) === $i + 1) {
						$error = RuleErrorBuilder::message(sprintf(
							'Parameter #%d $%s of method %s::%s() is not variadic.',
							$i + 1,
							$methodParameter->getName(),
							$method->getDeclaringClass()->getDisplayName(),
							$method->getName(),
						))->identifier('parameter.notVariadic');

						if (! $ignorable) {
							$error->nonIgnorable();
						}

						$messages[] = $error->build();
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

						$error = RuleErrorBuilder::message(sprintf(
							'Parameter #%d ...$%s (%s) of method %s::%s() is not contravariant with parameter #%d $%s (%s) of method %s::%s().',
							$i + 1,
							$methodParameter->getName(),
							$methodParameter->getNativeType()->describe(VerbosityLevel::typeOnly()),
							$method->getDeclaringClass()->getDisplayName(),
							$method->getName(),
							$i + $j + 1,
							$remainingPrototypeParameter->getName(),
							$remainingPrototypeParameter->getNativeType()->describe(VerbosityLevel::typeOnly()),
							$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
							$prototype->getName(),
						))->identifier('method.childParameterType');

						if (! $ignorable) {
							$error->nonIgnorable();
						}

						$messages[] = $error->build();
					}
					break;
				}
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is variadic but parameter #%d $%s of method %s::%s() is not variadic.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
				))->identifier('parameter.variadic');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $error->build();

				continue;
			}

			if ($prototypeParameter->isOptional() && !$methodParameter->isOptional()) {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is required but parameter #%d $%s of method %s::%s() is optional.',
					$i + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
				))->identifier('parameter.notOptional');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $error->build();
			}

			$methodParameterType = $methodParameter->getNativeType();

			if (!$prototypeParameter instanceof ParameterReflectionWithPhpDocs) {
				continue;
			}

			$prototypeParameterType = $prototypeParameter->getNativeType();
			if (!$this->phpVersion->supportsParameterTypeWidening()) {
				if (!$methodParameterType->equals($prototypeParameterType)) {
					$error = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s (%s) of method %s::%s() does not match parameter #%d $%s (%s) of method %s::%s().',
						$i + 1,
						$methodParameter->getName(),
						$methodParameterType->describe(VerbosityLevel::typeOnly()),
						$method->getDeclaringClass()->getDisplayName(),
						$method->getName(),
						$i + 1,
						$prototypeParameter->getName(),
						$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
						$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
						$prototype->getName(),
					))->identifier('method.childParameterType');

					if (! $ignorable) {
						$error->nonIgnorable();
					}

					$messages[] = $error->build();
				}
				continue;
			}

			if ($this->isParameterTypeCompatible($methodParameterType, $prototypeParameterType, $this->phpVersion->supportsParameterContravariance())) {
				continue;
			}

			if ($this->phpVersion->supportsParameterContravariance()) {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of method %s::%s() is not contravariant with parameter #%d $%s (%s) of method %s::%s().',
					$i + 1,
					$methodParameter->getName(),
					$methodParameterType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
				))->identifier('method.childParameterType');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $error->build();
			} else {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of method %s::%s() is not compatible with parameter #%d $%s (%s) of method %s::%s().',
					$i + 1,
					$methodParameter->getName(),
					$methodParameterType->describe(VerbosityLevel::typeOnly()),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					$prototype->getDeclaringClass()->getDisplayName($this->genericPrototypeMessage),
					$prototype->getName(),
				))->identifier('method.childParameterType');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $error->build();
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
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of method %s::%s() is not variadic.',
					$j + 1,
					$methodParameter->getName(),
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
				))->identifier('parameter.notVariadic');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $error->build();

				continue;
			}

			if ($methodParameter->isOptional()) {
				continue;
			}

			$error = RuleErrorBuilder::message(sprintf(
				'Parameter #%d $%s of method %s::%s() is not optional.',
				$j + 1,
				$methodParameter->getName(),
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			))->identifier('parameter.notOptional');

			if (! $ignorable) {
				$error->nonIgnorable();
			}

			$messages[] = $error->build();

			continue;
		}

		return $messages;
	}

	public function isParameterTypeCompatible(Type $methodParameterType, Type $prototypeParameterType, bool $supportsContravariance): bool
	{
		return $this->isTypeCompatible($methodParameterType, $prototypeParameterType, $supportsContravariance, false);
	}

	public function isReturnTypeCompatible(Type $methodParameterType, Type $prototypeParameterType, bool $supportsCovariance): bool
	{
		return $this->isTypeCompatible($methodParameterType, $prototypeParameterType, $supportsCovariance, true);
	}

	private function isTypeCompatible(Type $methodParameterType, Type $prototypeParameterType, bool $supportsContravariance, bool $considerMixedExplicitness): bool
	{
		if ($methodParameterType instanceof MixedType) {
			if ($considerMixedExplicitness && $prototypeParameterType instanceof MixedType) {
				return !$methodParameterType->isExplicitMixed() || $prototypeParameterType->isExplicitMixed();
			}

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
				if ($prototypeParameterType->isObject()->yes() && $prototypeParameterType->getObjectClassNames() === [Traversable::class]) {
					return true;
				}
			}

			return false;
		}

		return $methodParameterType->isSuperTypeOf($prototypeParameterType)->yes();
	}

}
