<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class FunctionCallParametersCheck
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private NullsafeCheck $nullsafeCheck;

	private PhpVersion $phpVersion;

	private bool $checkArgumentTypes;

	private bool $checkArgumentsPassedByReference;

	private bool $checkExtraArguments;

	private bool $checkMissingTypehints;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper,
		NullsafeCheck $nullsafeCheck,
		PhpVersion $phpVersion,
		bool $checkArgumentTypes,
		bool $checkArgumentsPassedByReference,
		bool $checkExtraArguments,
		bool $checkMissingTypehints
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->nullsafeCheck = $nullsafeCheck;
		$this->phpVersion = $phpVersion;
		$this->checkArgumentTypes = $checkArgumentTypes;
		$this->checkArgumentsPassedByReference = $checkArgumentsPassedByReference;
		$this->checkExtraArguments = $checkExtraArguments;
		$this->checkMissingTypehints = $checkMissingTypehints;
	}

	/**
	 * @param \PHPStan\Reflection\ParametersAcceptor $parametersAcceptor
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\New_ $funcCall
	 * @param array{string, string, string, string, string, string, string, string, string, string, string, string} $messages
	 * @return RuleError[]
	 */
	public function check(
		ParametersAcceptor $parametersAcceptor,
		Scope $scope,
		$funcCall,
		array $messages
	): array
	{
		$functionParametersMinCount = 0;
		$functionParametersMaxCount = 0;
		$parametersByName = [];
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
			if (!$parameter->isOptional()) {
				$functionParametersMinCount++;
			}

			$functionParametersMaxCount++;
		}

		if ($parametersAcceptor->isVariadic()) {
			$functionParametersMaxCount = -1;
		}

		/** @var array<int, array{Expr, Type, bool, string|null}> $arguments */
		$arguments = [];
		/** @var array<int, \PhpParser\Node\Arg> $args */
		$args = $funcCall->args;
		$hasNamedArguments = false;
		$errors = [];
		foreach ($args as $i => $arg) {
			$type = $scope->getType($arg->value);
			if ($hasNamedArguments && $arg->name === null) {
				$errors[] = RuleErrorBuilder::message('Named argument cannot be followed by a positional argument.')->line($arg->getLine())->nonIgnorable()->build();
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
						foreach ($arrays as $constantArray) {
							$types[] = $constantArray->getValueTypes()[$j];
						}
						$arguments[] = [
							$arg->value,
							TypeCombinator::union(...$types),
							false,
							$argumentName,
						];
					}
				} else {
					$arguments[] = [
						$arg->value,
						$type->getIterableValueType(),
						true,
						$argumentName,
					];
				}
				continue;
			}

			$arguments[] = [
				$arg->value,
				$type,
				false,
				$argumentName,
			];
		}

		if ($hasNamedArguments && !$this->phpVersion->supportsNamedArguments()) {
			$errors[] = RuleErrorBuilder::message('Named arguments are supported only on PHP 8.0 and later.')->nonIgnorable()->build();
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
					))->build();
				} elseif ($functionParametersMaxCount === -1 && $invokedParametersCount < $functionParametersMinCount) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[2] : $messages[3],
						$invokedParametersCount,
						$functionParametersMinCount
					))->build();
				} elseif ($functionParametersMaxCount !== -1) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						$invokedParametersCount === 1 ? $messages[4] : $messages[5],
						$invokedParametersCount,
						$functionParametersMinCount,
						$functionParametersMaxCount
					))->build();
				}
			}
		}

		if (
			$scope->getType($funcCall) instanceof VoidType
			&& !$scope->isInFirstLevelStatement()
			&& !$funcCall instanceof \PhpParser\Node\Expr\New_
		) {
			$errors[] = RuleErrorBuilder::message($messages[7])->build();
		}

		if (!$this->checkArgumentTypes && !$this->checkArgumentsPassedByReference) {
			return $errors;
		}

		$parameters = $parametersAcceptor->getParameters();
		$alreadyPassedParameters = [];

		foreach ($arguments as $i => [$argumentValue, $argumentValueType, $unpack, $argumentName]) {
			if ($this->checkArgumentTypes && $unpack) {
				$iterableTypeResult = $this->ruleLevelHelper->findTypeToCheck(
					$scope,
					$argumentValue,
					'',
					static function (Type $type): bool {
						return $type->isIterable()->yes();
					}
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
					))->build();
				}
			}

			if ($argumentName === null) {
				if (!isset($parameters[$i])) {
					if (!$parametersAcceptor->isVariadic() || count($parameters) === 0) {
						break;
					}

					$parameter = $parameters[count($parameters) - 1];
					if (!$parameter->isVariadic()) {
						break; // func_get_args
					}
				} else {
					$parameter = $parameters[$i];
				}
			} elseif (array_key_exists($argumentName, $parametersByName)) {
				$parameter = $parametersByName[$argumentName];
			} else {
				$errors[] = RuleErrorBuilder::message(sprintf($messages[11], $argumentName))->build();
				continue;
			}

			if (
				$hasNamedArguments
				&& array_key_exists($parameter->getName(), $alreadyPassedParameters)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf('Argument for parameter $%s has already been passed.', $parameter->getName()))->build();
				continue;
			}

			$alreadyPassedParameters[$parameter->getName()] = true;

			$parameterType = $parameter->getType();
			if (
				$this->checkArgumentTypes
				&& !$parameter->passedByReference()->createsNewVariable()
				&& !$this->ruleLevelHelper->accepts($parameterType, $argumentValueType, $scope->isDeclareStrictTypes())
			) {
				$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($parameterType);
				$errors[] = RuleErrorBuilder::message(sprintf(
					$messages[6],
					$i + 1,
					sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName()),
					$parameterType->describe($verbosityLevel),
					$argumentValueType->describe($verbosityLevel)
				))->build();
			}

			if (
				!$this->checkArgumentsPassedByReference
				|| !$parameter->passedByReference()->yes()
			) {
				continue;
			}

			if ($this->nullsafeCheck->containsNullSafe($argumentValue)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					$messages[8],
					$i + 1,
					sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName())
				))->build();
				continue;
			}

			if ($argumentValue instanceof \PhpParser\Node\Expr\Variable
				|| $argumentValue instanceof \PhpParser\Node\Expr\ArrayDimFetch
				|| $argumentValue instanceof \PhpParser\Node\Expr\PropertyFetch
				|| $argumentValue instanceof \PhpParser\Node\Expr\StaticPropertyFetch) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				$messages[8],
				$i + 1,
				sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName())
			))->build();
		}

		if ($hasNamedArguments) {
			foreach ($parameters as $parameter) {
				if ($parameter->isOptional()) {
					continue;
				}
				if (array_key_exists($parameter->getName(), $alreadyPassedParameters)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf($messages[10], sprintf('%s (%s)', $parameter->getName(), $parameter->getType()->describe(VerbosityLevel::typeOnly()))))->build();
			}
		}

		if ($this->checkMissingTypehints) {
			foreach ($parametersAcceptor->getResolvedTemplateTypeMap()->getTypes() as $name => $type) {
				if (!($type instanceof ErrorType) && !($type instanceof NeverType)) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf($messages[9], $name))->build();
			}
		}

		return $errors;
	}

}
