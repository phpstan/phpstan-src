<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class FunctionCallParametersCheck
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private bool $checkArgumentTypes;

	private bool $checkArgumentsPassedByReference;

	private bool $checkExtraArguments;

	private bool $checkMissingTypehints;

	public function __construct(
		RuleLevelHelper $ruleLevelHelper,
		bool $checkArgumentTypes,
		bool $checkArgumentsPassedByReference,
		bool $checkExtraArguments,
		bool $checkMissingTypehints
	)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->checkArgumentTypes = $checkArgumentTypes;
		$this->checkArgumentsPassedByReference = $checkArgumentsPassedByReference;
		$this->checkExtraArguments = $checkExtraArguments;
		$this->checkMissingTypehints = $checkMissingTypehints;
	}

	/**
	 * @param \PHPStan\Reflection\ParametersAcceptor $parametersAcceptor
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param \PhpParser\Node\Expr\FuncCall|\PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\New_ $funcCall
	 * @param string[] $messages Nine message templates
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
		foreach ($parametersAcceptor->getParameters() as $parameter) {
			if (!$parameter->isOptional()) {
				$functionParametersMinCount++;
			}

			$functionParametersMaxCount++;
		}

		if ($parametersAcceptor->isVariadic()) {
			$functionParametersMaxCount = -1;
		}

		$errors = [];
		$invokedParametersCount = count($funcCall->args);
		foreach ($funcCall->args as $arg) {
			if ($arg->unpack) {
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

		/** @var array<int, \PhpParser\Node\Arg> $args */
		$args = $funcCall->args;
		foreach ($args as $i => $argument) {
			if ($this->checkArgumentTypes && $argument->unpack) {
				$iterableTypeResult = $this->ruleLevelHelper->findTypeToCheck(
					$scope,
					$argument->value,
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

			$parameterType = $parameter->getType();

			$argumentValueType = $scope->getType($argument->value);
			if ($argument->unpack) {
				$argumentValueType = $argumentValueType->getIterableValueType();
			}
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
				|| $argument->value instanceof \PhpParser\Node\Expr\Variable
				|| $argument->value instanceof \PhpParser\Node\Expr\ArrayDimFetch
				|| $argument->value instanceof \PhpParser\Node\Expr\PropertyFetch
				|| $argument->value instanceof \PhpParser\Node\Expr\StaticPropertyFetch
			) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				$messages[8],
				$i + 1,
				sprintf('%s$%s', $parameter->isVariadic() ? '...' : '', $parameter->getName())
			))->build();
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
