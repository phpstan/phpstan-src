<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
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
use function ucfirst;

#[AutowiredService]
final class MethodParameterComparisonHelper
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function compare(
		ExtendedMethodReflection $prototype,
		ClassReflection $prototypeDeclaringClass,
		ExtendedMethodReflection $method,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		bool $ignorable,
		?int $line = null,
	): array
	{
		/** @var list<IdentifierRuleError> $messages */
		$messages = [];
		$prototypeVariant = $prototype->getVariants()[0];

		$methodParameters = $method->getOnlyVariant()->getParameters();

		$prototypeAfterVariadic = false;
		foreach ($prototypeVariant->getParameters() as $i => $prototypeParameter) {
			if (!array_key_exists($i, $methodParameters)) {
				$error = RuleErrorBuilder::message(sprintf(
					'%s overrides %s but misses parameter #%d $%s.',
					ucfirst(OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass())),
					OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
					$i + 1,
					$prototypeParameter->getName(),
				))->identifier('parameter.missing');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $this->buildError($error, $line);

				continue;
			}

			$methodParameter = $methodParameters[$i];
			if ($prototype->acceptsNamedArguments()->yes()) {
				if ($prototypeParameter->getName() !== $methodParameter->getName()) {
					$scope->emitCollectedData(OverridingMethodRenamesParameterCollector::class, [
						$prototypeDeclaringClass->getName(),
						$prototype->getName(),
						$method->getDeclaringClass()->getName(),
						$prototypeParameter->getName(),
						$methodParameter->getName(),
					]);
				}
			}
			if ($prototypeParameter->passedByReference()->no()) {
				if (!$methodParameter->passedByReference()->no()) {
					$error = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s of %s is passed by reference but parameter #%d $%s of %s is not passed by reference.',
						$i + 1,
						$methodParameter->getName(),
						OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
						$i + 1,
						$prototypeParameter->getName(),
						OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
					))->identifier('parameter.byRef');

					if (! $ignorable) {
						$error->nonIgnorable();
					}

					$messages[] = $this->buildError($error, $line);
				}
			} elseif ($methodParameter->passedByReference()->no()) {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of %s is not passed by reference but parameter #%d $%s of %s is passed by reference.',
					$i + 1,
					$methodParameter->getName(),
					OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
					$i + 1,
					$prototypeParameter->getName(),
					OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
				))->identifier('parameter.notByRef');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $this->buildError($error, $line);
			}

			if ($prototypeParameter->isVariadic()) {
				$prototypeAfterVariadic = true;
				if (!$methodParameter->isVariadic()) {
					if (!$methodParameter->isOptional()) {
						if (count($methodParameters) !== $i + 1) {
							$error = RuleErrorBuilder::message(sprintf(
								'Parameter #%d $%s of %s is not optional.',
								$i + 1,
								$methodParameter->getName(),
								OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
							))->identifier('parameter.notOptional');

							if (! $ignorable) {
								$error->nonIgnorable();
							}

							$messages[] = $this->buildError($error, $line);

							continue;
						}

						$error = RuleErrorBuilder::message(sprintf(
							'Parameter #%d $%s of %s is not variadic but parameter #%d $%s of %s is variadic.',
							$i + 1,
							$methodParameter->getName(),
							OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
							$i + 1,
							$prototypeParameter->getName(),
							OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
						))->identifier('parameter.notVariadic');

						if (! $ignorable) {
							$error->nonIgnorable();
						}

						$messages[] = $this->buildError($error, $line);

						continue;
					} elseif (count($methodParameters) === $i + 1) {
						$error = RuleErrorBuilder::message(sprintf(
							'Parameter #%d $%s of %s is not variadic.',
							$i + 1,
							$methodParameter->getName(),
							OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
						))->identifier('parameter.notVariadic');

						if (! $ignorable) {
							$error->nonIgnorable();
						}

						$messages[] = $this->buildError($error, $line);
					}
				}
			} elseif ($methodParameter->isVariadic()) {
				if ($this->phpVersion->supportsLessOverridenParametersWithVariadic()) {
					$remainingPrototypeParameters = array_slice($prototypeVariant->getParameters(), $i);
					foreach ($remainingPrototypeParameters as $j => $remainingPrototypeParameter) {
						if ($methodParameter->getNativeType()->isSuperTypeOf($remainingPrototypeParameter->getNativeType())->yes()) {
							continue;
						}

						$error = RuleErrorBuilder::message(sprintf(
							'Parameter #%d ...$%s (%s) of %s is not contravariant with parameter #%d $%s (%s) of %s.',
							$i + 1,
							$methodParameter->getName(),
							$methodParameter->getNativeType()->describe(VerbosityLevel::typeOnly()),
							OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
							$i + $j + 1,
							$remainingPrototypeParameter->getName(),
							$remainingPrototypeParameter->getNativeType()->describe(VerbosityLevel::typeOnly()),
							OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
						))->identifier('method.childParameterType');

						if (! $ignorable) {
							$error->nonIgnorable();
						}

						$messages[] = $this->buildError($error, $line);
					}
					break;
				}
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of %s is variadic but parameter #%d $%s of %s is not variadic.',
					$i + 1,
					$methodParameter->getName(),
					OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
					$i + 1,
					$prototypeParameter->getName(),
					OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
				))->identifier('parameter.variadic');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $this->buildError($error, $line);

				continue;
			}

			if ($prototypeParameter->isOptional() && !$methodParameter->isOptional()) {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s of %s is required but parameter #%d $%s of %s is optional.',
					$i + 1,
					$methodParameter->getName(),
					OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
					$i + 1,
					$prototypeParameter->getName(),
					OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
				))->identifier('parameter.notOptional');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $this->buildError($error, $line);
			}

			$methodParameterType = $methodParameter->getNativeType();

			$prototypeParameterType = $prototypeParameter->getNativeType();
			if (!$this->phpVersion->supportsParameterTypeWidening()) {
				if (!$methodParameterType->equals($prototypeParameterType)) {
					$error = RuleErrorBuilder::message(sprintf(
						'Parameter #%d $%s (%s) of %s does not match parameter #%d $%s (%s) of %s.',
						$i + 1,
						$methodParameter->getName(),
						$methodParameterType->describe(VerbosityLevel::typeOnly()),
						OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
						$i + 1,
						$prototypeParameter->getName(),
						$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
						OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
					))->identifier('method.childParameterType');

					if (! $ignorable) {
						$error->nonIgnorable();
					}

					$messages[] = $this->buildError($error, $line);
				}
				continue;
			}

			if ($this->isParameterTypeCompatible($methodParameterType, $prototypeParameterType, $this->phpVersion->supportsParameterContravariance())) {
				continue;
			}

			if ($this->phpVersion->supportsParameterContravariance()) {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of %s is not contravariant with parameter #%d $%s (%s) of %s.',
					$i + 1,
					$methodParameter->getName(),
					$methodParameterType->describe(VerbosityLevel::typeOnly()),
					OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
				))->identifier('method.childParameterType');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $this->buildError($error, $line);
			} else {
				$error = RuleErrorBuilder::message(sprintf(
					'Parameter #%d $%s (%s) of %s is not compatible with parameter #%d $%s (%s) of %s.',
					$i + 1,
					$methodParameter->getName(),
					$methodParameterType->describe(VerbosityLevel::typeOnly()),
					OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
					$i + 1,
					$prototypeParameter->getName(),
					$prototypeParameterType->describe(VerbosityLevel::typeOnly()),
					OverridingMethodCheck::describeMethod($prototype, $prototypeDeclaringClass, true),
				))->identifier('method.childParameterType');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $this->buildError($error, $line);
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
					'Parameter #%d $%s of %s is not variadic.',
					$j + 1,
					$methodParameter->getName(),
					OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
				))->identifier('parameter.notVariadic');

				if (! $ignorable) {
					$error->nonIgnorable();
				}

				$messages[] = $this->buildError($error, $line);

				continue;
			}

			if ($methodParameter->isOptional()) {
				continue;
			}

			$error = RuleErrorBuilder::message(sprintf(
				'Parameter #%d $%s of %s is not optional.',
				$j + 1,
				$methodParameter->getName(),
				OverridingMethodCheck::describeMethod($method, $method->getDeclaringClass()),
			))->identifier('parameter.notOptional');

			if (! $ignorable) {
				$error->nonIgnorable();
			}

			$messages[] = $this->buildError($error, $line);
		}

		return $messages;
	}

	private function isParameterTypeCompatible(Type $methodParameterType, Type $prototypeParameterType, bool $supportsContravariance): bool
	{
		return $this->isTypeCompatible($methodParameterType, $prototypeParameterType, $supportsContravariance, false);
	}

	/**
	 * @param RuleErrorBuilder<IdentifierRuleError> $error
	 */
	private function buildError(RuleErrorBuilder $error, ?int $line): IdentifierRuleError
	{
		if ($line !== null) {
			$error->line($line);
		}

		return $error->build();
	}

	public function isReturnTypeCompatible(Type $methodParameterType, Type $prototypeParameterType, bool $supportsCovariance): bool
	{
		// void can only override void or a missing return type, mixed does not include it
		if (
			$methodParameterType instanceof MixedType
			&& $methodParameterType->isExplicitMixed()
			&& $prototypeParameterType->isVoid()->yes()
		) {
			return false;
		}

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
				if ($prototypeParameterType instanceof ConstantArrayType) {
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
