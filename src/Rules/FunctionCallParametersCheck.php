<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ConditionalType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_fill;
use function array_key_exists;
use function count;
use function is_string;
use function max;
use function sprintf;

class FunctionCallParametersCheck
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private NullsafeCheck $nullsafeCheck,
		private PhpVersion $phpVersion,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private PropertyReflectionFinder $propertyReflectionFinder,
		private bool $checkArgumentTypes,
		private bool $checkArgumentsPassedByReference,
		private bool $checkExtraArguments,
		private bool $checkMissingTypehints,
		private bool $checkUnresolvableParameterTypes,
	)
	{
	}

	/**
	 * @param Node\Expr\FuncCall|Node\Expr\MethodCall|Node\Expr\StaticCall|Node\Expr\New_ $funcCall
	 * @param array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string, 8: string, 9: string, 10: string, 11: string, 12: string, 13?: string} $messages
	 * @return RuleError[]
	 */
	public function check(
		ParametersAcceptor $parametersAcceptor,
		Scope $scope,
		bool $isBuiltin,
		$funcCall,
		array $messages,
	): array
	{
		$functionParametersMinCount = 0;
		$functionParametersMaxCount = 0;
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			if (!$parameter->isOptional()) {
				$functionParametersMinCount++;
			}

			$functionParametersMaxCount++;
		}

		if ($parametersAcceptor->isVariadic()) {
			$functionParametersMaxCount = -1;
		}

		/** @var array<int, array{Expr, Type, bool, string|null, int}> $arguments */
		$arguments = [];
		/** @var array<int, Node\Arg> $args */
		$args = $funcCall->getArgs();
		$hasNamedArguments = false;
		$hasUnpackedArgument = false;
		$errors = [];
		foreach ($args as $i => $arg) {
			$type = $scope->getType($arg->value);
			if ($hasNamedArguments && $arg->unpack) {
				$errors[] = RuleErrorBuilder::message('Named argument cannot be followed by an unpacked (...) argument.')->line($arg->getLine())->nonIgnorable()->build();
			}
			if ($hasUnpackedArgument && !$arg->unpack) {
				$errors[] = RuleErrorBuilder::message('Unpacked argument (...) cannot be followed by a non-unpacked argument.')->line($arg->getLine())->nonIgnorable()->build();
			}
			if ($arg->unpack) {
				$hasUnpackedArgument = true;
			}
			$argumentName = null;
			if ($arg->name !== null) {
				$hasNamedArguments = true;
				$argumentName = $arg->name->toString();
			}
			if ($arg->unpack) {
				$arrays = $type->getConstantArrays();
				if (count($arrays) > 0) {
					$minKeys = null;
					foreach ($arrays as $array) {
						$countType = $array->getArraySize();
						if ($countType instanceof ConstantIntegerType) {
							$keysCount = $countType->getValue();
						} elseif ($countType instanceof IntegerRangeType) {
							$keysCount = $countType->getMin();
							if ($keysCount === null) {
								throw new ShouldNotHappenException();
							}
						} else {
							throw new ShouldNotHappenException();
						}
						if ($minKeys !== null && $keysCount >= $minKeys) {
							continue;
						}

						$minKeys = $keysCount;
					}

					for ($j = 0; $j < $minKeys; $j++) {
						$types = [];
						$commonKey = null;
						foreach ($arrays as $constantArray) {
							$types[] = $constantArray->getValueTypes()[$j];
							$keyType = $constantArray->getKeyTypes()[$j];
							if ($commonKey === null) {
								$commonKey = $keyType->getValue();
							} elseif ($commonKey !== $keyType->getValue()) {
								$commonKey = false;
							}
						}
						$keyArgumentName = null;
						if (is_string($commonKey)) {
							$keyArgumentName = $commonKey;
							$hasNamedArguments = true;
						}
						$arguments[] = [
							$arg->value,
							TypeCombinator::union(...$types),
							false,
							$keyArgumentName,
							$arg->getLine(),
						];
					}
				} else {
					$arguments[] = [
						$arg->value,
						$type->getIterableValueType(),
						true,
						null,
						$arg->getLine(),
					];
				}
				continue;
			}

			$arguments[] = [
				$arg->value,
				$type,
				false,
				$argumentName,
				$arg->getLine(),
			];
		}

		if ($hasNamedArguments && !$this->phpVersion->supportsNamedArguments() && !(bool) $funcCall->getAttribute('isAttribute', false)) {
			$errors[] = RuleErrorBuilder::message('Named arguments are supported only on PHP 8.0 and later.')->line($funcCall->getLine())->nonIgnorable()->build();
		}

		if (!$hasNamedArguments) {
			$invokedParametersCount = count($arguments);
			foreach ($arguments as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName]) {
				if ($unpack) {
					$invokedParametersCount = max($functionParametersMinCount, $functionParametersMaxCount);
					break;
				}
			}

			if (
				$invokedParametersCount < $functionParametersMinCount
				|| ($this->checkExtraArguments && $invokedParametersCount > $functionParametersMaxCount)
			) {
				if ($functionParametersMinCount === $functionParametersMaxCount) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[0] : $messages[1],
						$invokedParametersCount,
						$functionParametersMinCount,
					))->line($funcCall->getLine())->build();
				} elseif ($functionParametersMaxCount === -1 && $invokedParametersCount < $functionParametersMinCount) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[2] : $messages[3],
						$invokedParametersCount,
						$functionParametersMinCount,
					))->line($funcCall->getLine())->build();
				} elseif ($functionParametersMaxCount !== -1) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[4] : $messages[5],
						$invokedParametersCount,
						$functionParametersMinCount,
						$functionParametersMaxCount,
					))->line($funcCall->getLine())->build();
				}
			}
		}

		if (
			!$funcCall instanceof Node\Expr\New_
			&& !$scope->isInFirstLevelStatement()
			&& $scope->getKeepVoidType($funcCall)->isVoid()->yes()
		) {
			$errors[] = RuleErrorBuilder::message($messages[7])->line($funcCall->getLine())->build();
		}

		[$addedErrors, $argumentsWithParameters] = $this->processArguments($parametersAcceptor, $funcCall->getLine(), $isBuiltin, $arguments, $hasNamedArguments, $messages[10], $messages[11]);
		foreach ($addedErrors as $error) {
			$errors[] = $error;
		}

		if (!$this->checkArgumentTypes && !$this->checkArgumentsPassedByReference) {
			return $errors;
		}

		foreach ($argumentsWithParameters as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, $parameter, $originalParameter]) {
			if ($this->checkArgumentTypes && $unpack) {
				$iterableTypeResult = $this->ruleLevelHelper->findTypeToCheck(
					$scope,
					$argumentValue,
					'',
					static fn (Type $type): bool => $type->isIterable()->yes(),
				);
				$iterableTypeResultType = $iterableTypeResult->getType();
				if (
					!$iterableTypeResultType instanceof ErrorType
					&& !$iterableTypeResultType->isIterable()->yes()
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Only iterables can be unpacked, %s given in argument #%d.',
						$iterableTypeResultType->describe(VerbosityLevel::typeOnly()),
						$i + 1,
					))->line($argumentLine)->build();
				}
			}

			if ($parameter === null) {
				continue;
			}

			if ($this->checkArgumentTypes) {
				$parameterType = TypeUtils::resolveLateResolvableTypes($parameter->getType());

				if (!$parameter->passedByReference()->createsNewVariable() || !$isBuiltin) {
					$accepts = $this->ruleLevelHelper->acceptsWithReason($parameterType, $argumentValueType, $scope->isDeclareStrictTypes());

					if (!$accepts->result) {
						$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType, $argumentValueType);
						$parameterDescription = sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName());
						$errors[] = RuleErrorBuilder::message(sprintf(
							$messages[6],
							$argumentName === null ? sprintf(
								'#%d %s',
								$i + 1,
								$parameterDescription,
							) : $parameterDescription,
							$parameterType->describe($verbosityLevel),
							$argumentValueType->describe($verbosityLevel),
						))->line($argumentLine)->acceptsReasonsTip($accepts->reasons)->build();
					}
				}

				if ($this->checkUnresolvableParameterTypes
					&& $originalParameter !== null
					&& isset($messages[13])
					&& !$this->unresolvableTypeHelper->containsUnresolvableType($originalParameter->getType())
					&& $this->unresolvableTypeHelper->containsUnresolvableType($parameterType)
				) {
					$parameterDescription = sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName());
					$errors[] = RuleErrorBuilder::message(sprintf(
						$messages[13],
						$argumentName === null ? sprintf(
							'#%d %s',
							$i + 1,
							$parameterDescription,
						) : $parameterDescription,
					))->line($argumentLine)->build();
				}
			}

			if (
				!$this->checkArgumentsPassedByReference
				|| !$parameter->passedByReference()->yes()
			) {
				continue;
			}

			if ($this->nullsafeCheck->containsNullSafe($argumentValue)) {
				$parameterDescription = sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName());
				$errors[] = RuleErrorBuilder::message(sprintf(
					$messages[8],
					$argumentName === null ? sprintf('#%d %s', $i + 1, $parameterDescription) : $parameterDescription,
				))->line($argumentLine)->build();
				continue;
			}

			if (
				$argumentValue instanceof Node\Expr\PropertyFetch
				|| $argumentValue instanceof Node\Expr\StaticPropertyFetch) {
				$propertyReflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($argumentValue, $scope);
				foreach ($propertyReflections as $propertyReflection) {
					$nativePropertyReflection = $propertyReflection->getNativeReflection();
					if ($nativePropertyReflection === null) {
						continue;
					}
					if (!$nativePropertyReflection->isReadOnly()) {
						continue;
					}

					if ($nativePropertyReflection->isStatic()) {
						$propertyDescription = sprintf('static readonly property %s::$%s', $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyReflection->getName());
					} else {
						$propertyDescription = sprintf('readonly property %s::$%s', $propertyReflection->getDeclaringClass()->getDisplayName(), $propertyReflection->getName());
					}

					$parameterDescription = sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName());
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Parameter %s is passed by reference so it does not accept %s.',
						$argumentName === null ? sprintf('#%d %s', $i + 1, $parameterDescription) : $parameterDescription,
						$propertyDescription,
					))->line($argumentLine)->build();
				}
			}

			if ($argumentValue instanceof Node\Expr\Variable
				|| $argumentValue instanceof Node\Expr\ArrayDimFetch
				|| $argumentValue instanceof Node\Expr\PropertyFetch
				|| $argumentValue instanceof Node\Expr\StaticPropertyFetch) {
				continue;
			}

			$parameterDescription = sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName());
			$errors[] = RuleErrorBuilder::message(sprintf(
				$messages[8],
				$argumentName === null ? sprintf('#%d %s', $i + 1, $parameterDescription) : $parameterDescription,
			))->line($argumentLine)->build();
		}

		if ($this->checkMissingTypehints && $parametersAcceptor instanceof ResolvedFunctionVariant) {
			$originalParametersAcceptor = $parametersAcceptor->getOriginalParametersAcceptor();
			$resolvedTypes = $parametersAcceptor->getResolvedTemplateTypeMap()->getTypes();
			if (count($resolvedTypes) > 0) {
				$returnTemplateTypes = [];
				TypeTraverser::map(
					$parametersAcceptor->getReturnTypeWithUnresolvableTemplateTypes(),
					static function (Type $type, callable $traverse) use (&$returnTemplateTypes): Type {
						while ($type instanceof ConditionalType && $type->isResolvable()) {
							$type = $type->resolve();
						}

						if ($type instanceof TemplateType) {
							$returnTemplateTypes[$type->getName()] = true;
							return $type;
						}

						return $traverse($type);
					},
				);

				$parameterTemplateTypes = [];
				foreach ($originalParametersAcceptor->getParameters() as $parameter) {
					TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use (&$parameterTemplateTypes): Type {
						if ($type instanceof TemplateType) {
							$parameterTemplateTypes[$type->getName()] = true;
							return $type;
						}

						return $traverse($type);
					});
				}

				foreach ($resolvedTypes as $name => $type) {
					if (
						!($type instanceof ErrorType)
						&& (
							!$type instanceof NeverType
							|| $type->isExplicit()
						)
					) {
						continue;
					}

					if (!array_key_exists($name, $returnTemplateTypes)) {
						continue;
					}

					if (!array_key_exists($name, $parameterTemplateTypes)) {
						continue;
					}

					$errors[] = RuleErrorBuilder::message(sprintf($messages[9], $name))->line($funcCall->getLine())->tip('See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type')->build();
				}
			}

			if (
				!$this->unresolvableTypeHelper->containsUnresolvableType($originalParametersAcceptor->getReturnType())
				&& $this->unresolvableTypeHelper->containsUnresolvableType($parametersAcceptor->getReturnType())
			) {
				$errors[] = RuleErrorBuilder::message($messages[12])->line($funcCall->getLine())->build();
			}
		}

		return $errors;
	}

	/**
	 * @param array<int, array{Expr, Type, bool, string|null, int}> $arguments
	 * @return array{RuleError[], array<int, array{Expr, Type, bool, (string|null), int, (ParameterReflection|null), (ParameterReflection|null)}>}
	 */
	private function processArguments(
		ParametersAcceptor $parametersAcceptor,
		int $line,
		bool $isBuiltin,
		array $arguments,
		bool $hasNamedArguments,
		string $missingParameterMessage,
		string $unknownParameterMessage,
	): array
	{
		$parameters = $parametersAcceptor->getParameters();
		$originalParameters = $parametersAcceptor instanceof ResolvedFunctionVariant
			? $parametersAcceptor->getOriginalParametersAcceptor()->getParameters()
			: array_fill(0, count($parameters), null);
		$parametersByName = [];
		$originalParametersByName = [];
		$unusedParametersByName = [];
		$errors = [];
		foreach ($parameters as $i => $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
			$originalParametersByName[$parameter->getName()] = $originalParameters[$i];

			if ($parameter->isVariadic()) {
				continue;
			}

			$unusedParametersByName[$parameter->getName()] = $parameter;
		}

		$newArguments = [];

		$namedArgumentAlreadyOccurred = false;
		foreach ($arguments as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine]) {
			if ($argumentName === null) {
				if (!isset($parameters[$i])) {
					if (!$parametersAcceptor->isVariadic() || count($parameters) === 0) {
						$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
						break;
					}

					$parameter = $parameters[count($parameters) - 1];
					$originalParameter = $originalParameters[count($originalParameters) - 1];
					if (!$parameter->isVariadic()) {
						$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
						break; // func_get_args
					}
				} else {
					$parameter = $parameters[$i];
					$originalParameter = $originalParameters[$i];
				}
			} elseif (array_key_exists($argumentName, $parametersByName)) {
				$namedArgumentAlreadyOccurred = true;
				$parameter = $parametersByName[$argumentName];
				$originalParameter = $originalParametersByName[$argumentName];
			} else {
				$namedArgumentAlreadyOccurred = true;

				$parametersCount = count($parameters);
				if (
					!$parametersAcceptor->isVariadic()
					|| $parametersCount <= 0
					|| $isBuiltin
				) {
					$errors[] = RuleErrorBuilder::message(sprintf($unknownParameterMessage, $argumentName))->line($argumentLine)->build();
					$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
					continue;
				}

				$parameter = $parameters[$parametersCount - 1];
				$originalParameter = $originalParameters[$parametersCount - 1];
			}

			if ($namedArgumentAlreadyOccurred && $argumentName === null && !$unpack) {
				$errors[] = RuleErrorBuilder::message('Named argument cannot be followed by a positional argument.')->line($argumentLine)->nonIgnorable()->build();
				$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null, null];
				continue;
			}

			$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, $parameter, $originalParameter];

			if (
				$hasNamedArguments
				&& !$parameter->isVariadic()
				&& !array_key_exists($parameter->getName(), $unusedParametersByName)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf('Argument for parameter $%s has already been passed.', $parameter->getName()))->line($argumentLine)->build();
				continue;
			}

			unset($unusedParametersByName[$parameter->getName()]);
		}

		if ($hasNamedArguments) {
			foreach ($unusedParametersByName as $parameter) {
				if ($parameter->isOptional()) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf($missingParameterMessage, sprintf('%s (%s)', $parameter->getName(), $parameter->getType()->describe(VerbosityLevel::typeOnly()))))->line($line)->build();
			}
		}

		return [$errors, $newArguments];
	}

}
