<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class FunctionCallParametersCheck
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private NullsafeCheck $nullsafeCheck;

	private PhpVersion $phpVersion;

	private UnresolvableTypeHelper $unresolvableTypeHelper;

	private bool $checkArgumentTypes;

	private bool $checkArgumentsPassedByReference;

	private bool $checkExtraArguments;

	private bool $checkMissingTypehints;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper,
		NullsafeCheck $nullsafeCheck,
		PhpVersion $phpVersion,
		UnresolvableTypeHelper $unresolvableTypeHelper,
		bool $checkArgumentTypes,
		bool $checkArgumentsPassedByReference,
		bool $checkExtraArguments,
		bool $checkMissingTypehints
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->nullsafeCheck = $nullsafeCheck;
		$this->phpVersion = $phpVersion;
		$this->unresolvableTypeHelper = $unresolvableTypeHelper;
		$this->checkArgumentTypes = $checkArgumentTypes;
		$this->checkArgumentsPassedByReference = $checkArgumentsPassedByReference;
		$this->checkExtraArguments = $checkExtraArguments;
		$this->checkMissingTypehints = $checkMissingTypehints;
	}

	/**
	 * @param \PHPStan\Reflection\ParametersAcceptor $parametersAcceptor
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\New_ $funcCall
	 * @param array{string, string, string, string, string, string, string, string, string, string, string, string, string} $messages
	 * @return RuleError[]
	 */
	public function check(
		ParametersAcceptor $parametersAcceptor,
		Scope $scope,
		bool $isBuiltin,
		$funcCall,
		array $messages
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
		/** @var array<int, \PhpParser\Node\Arg> $args */
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
				$arrays = TypeUtils::getConstantArrays($type);
				if (count($arrays) > 0) {
					$minKeys = null;
					foreach ($arrays as $array) {
						$keysCount = count($array->getKeyTypes());
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

		if ($hasNamedArguments && !$this->phpVersion->supportsNamedArguments()) {
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
						$functionParametersMinCount
					))->line($funcCall->getLine())->build();
				} elseif ($functionParametersMaxCount === -1 && $invokedParametersCount < $functionParametersMinCount) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[2] : $messages[3],
						$invokedParametersCount,
						$functionParametersMinCount
					))->line($funcCall->getLine())->build();
				} elseif ($functionParametersMaxCount !== -1) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[4] : $messages[5],
						$invokedParametersCount,
						$functionParametersMinCount,
						$functionParametersMaxCount
					))->line($funcCall->getLine())->build();
				}
			}
		}

		if (
			$scope->getType($funcCall) instanceof VoidType
			&& !$scope->isInFirstLevelStatement()
			&& !$funcCall instanceof \PhpParser\Node\Expr\New_
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

		foreach ($argumentsWithParameters as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, $parameter]) {
			if ($this->checkArgumentTypes && $unpack) {
				$iterableTypeResult = $this->ruleLevelHelper->findTypeToCheck(
					$scope,
					$argumentValue,
					'',
					static function (Type $type): bool {
						return $type->isIterable()->yes();
					},
					false
				);
				$iterableTypeResultType = $iterableTypeResult->getType();
				if (
					!$iterableTypeResultType instanceof ErrorType
					&& !$iterableTypeResultType->isIterable()->yes()
				) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Only iterables can be unpacked, %s given in argument #%d.',
						$iterableTypeResultType->describe(VerbosityLevel::typeOnly()),
						$i + 1
					))->line($argumentLine)->build();
				}
			}

			if ($parameter === null) {
				continue;
			}

			$parameterType = $parameter->getType();
			if (
				$this->checkArgumentTypes
				&& !$parameter->passedByReference()->createsNewVariable()
				&& !$this->ruleLevelHelper->accepts($parameterType, $argumentValueType, $scope->isDeclareStrictTypes())
			) {
				$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType, $argumentValueType);
				$parameterDescription = sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName());
				$errors[] = RuleErrorBuilder::message(sprintf(
					$messages[6],
					$argumentName === null ? sprintf(
						'#%d %s',
						$i + 1,
						$parameterDescription
					) : $parameterDescription,
					$parameterType->describe($verbosityLevel),
					$argumentValueType->describe($verbosityLevel)
				))->line($argumentLine)->build();
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
					$argumentName === null ? sprintf('#%d %s', $i + 1, $parameterDescription) : $parameterDescription
				))->line($argumentLine)->build();
				continue;
			}

			if ($argumentValue instanceof \PhpParser\Node\Expr\Variable
				|| $argumentValue instanceof \PhpParser\Node\Expr\ArrayDimFetch
				|| $argumentValue instanceof \PhpParser\Node\Expr\PropertyFetch
				|| $argumentValue instanceof \PhpParser\Node\Expr\StaticPropertyFetch) {
				continue;
			}

			$parameterDescription = sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName());
			$errors[] = RuleErrorBuilder::message(sprintf(
				$messages[8],
				$argumentName === null ? sprintf('#%d %s', $i + 1, $parameterDescription) : $parameterDescription
			))->line($argumentLine)->build();
		}

		if ($this->checkMissingTypehints && $parametersAcceptor instanceof ResolvedFunctionVariant) {
			$originalParametersAcceptor = $parametersAcceptor->getOriginalParametersAcceptor();
			$resolvedTypes = $parametersAcceptor->getResolvedTemplateTypeMap()->getTypes();
			if (count($resolvedTypes) > 0) {
				$returnTemplateTypes = [];
				TypeTraverser::map($originalParametersAcceptor->getReturnType(), static function (Type $type, callable $traverse) use (&$returnTemplateTypes): Type {
					if ($type instanceof TemplateType) {
						$returnTemplateTypes[$type->getName()] = true;
						return $type;
					}

					return $traverse($type);
				});

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
	 * @param ParametersAcceptor $parametersAcceptor
	 * @param array<int, array{Expr, Type, bool, string|null, int}> $arguments
	 * @param bool $hasNamedArguments
	 * @param string $missingParameterMessage
	 * @param string $unknownParameterMessage
	 * @return array{RuleError[], array<int, array{Expr, Type, bool, string|null, int, \PHPStan\Reflection\ParameterReflection|null}>}
	 */
	private function processArguments(
		ParametersAcceptor $parametersAcceptor,
		int $line,
		bool $isBuiltin,
		array $arguments,
		bool $hasNamedArguments,
		string $missingParameterMessage,
		string $unknownParameterMessage
	): array
	{
		$parameters = $parametersAcceptor->getParameters();
		$parametersByName = [];
		$unusedParametersByName = [];
		$errors = [];
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;

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
						$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null];
						break;
					}

					$parameter = $parameters[count($parameters) - 1];
					if (!$parameter->isVariadic()) {
						$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null];
						break; // func_get_args
					}
				} else {
					$parameter = $parameters[$i];
				}
			} elseif (array_key_exists($argumentName, $parametersByName)) {
				$namedArgumentAlreadyOccurred = true;
				$parameter = $parametersByName[$argumentName];
			} else {
				$namedArgumentAlreadyOccurred = true;

				$parametersCount = count($parameters);
				if (
					!$parametersAcceptor->isVariadic()
					|| $parametersCount <= 0
					|| $isBuiltin
				) {
					$errors[] = RuleErrorBuilder::message(sprintf($unknownParameterMessage, $argumentName))->line($argumentLine)->build();
					$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null];
					continue;
				}

				$parameter = $parameters[$parametersCount - 1];
			}

			if ($namedArgumentAlreadyOccurred && $argumentName === null && !$unpack) {
				$errors[] = RuleErrorBuilder::message('Named argument cannot be followed by a positional argument.')->line($argumentLine)->nonIgnorable()->build();
				$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, null];
				continue;
			}

			$newArguments[$i] = [$argumentValue, $argumentValueType, $unpack, $argumentName, $argumentLine, $parameter];

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
