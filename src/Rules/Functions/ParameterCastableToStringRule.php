<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ParameterCastableToStringCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_slice;
use function count;
use function in_array;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
#[RegisteredRule(level: 5)]
final class ParameterCastableToStringRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ParameterCastableToStringCheck $parameterCastableToStringCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		$functionName = $functionReflection->getName();
		$checkAllArgsFunctions = ['array_intersect', 'array_intersect_assoc', 'array_diff', 'array_diff_assoc'];
		$checkAllArgsExceptLastFunctions = ['array_diff_uassoc', 'array_intersect_uassoc'];
		$checkArgsFunctions = [
			'array_combine' => [0],
			'natcasesort' => [0],
			'natsort' => [0],
			'array_count_values' => [0],
			'array_fill_keys' => [0],
			'strtr' => [1],
			'substr_replace' => [0, 1],
			'preg_grep' => [1],
			'preg_replace_callback_array' => [1],
			'setlocale' => [1],
			'proc_open' => [0, 4],
			'pcntl_exec' => [1, 2],
		];

		if (
			!in_array($functionName, $checkAllArgsFunctions, true)
			&& !in_array($functionName, $checkAllArgsExceptLastFunctions, true)
			&& !array_key_exists($functionName, $checkArgsFunctions)
		) {
			return [];
		}

		$origArgs = $node->getArgs();
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$origArgs,
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$errorMessage = 'Parameter %s of function %s expects an array of values castable to string, %s given.';

		if (in_array($functionName, $checkAllArgsFunctions, true)) {
			$argsToCheck = $origArgs;
		} elseif (in_array($functionName, $checkAllArgsExceptLastFunctions, true)) {
			$argsToCheck = [];
			$lastArgIdx = count($origArgs) - 1;
			foreach ($origArgs as $argIdx => $arg) {
				if ($arg->unpack) {
					return [];
				}
				if ($argIdx === $lastArgIdx) {
					break;
				}
				$argsToCheck[$argIdx] = $arg;
			}
		} else {
			$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);

			if ($normalizedFuncCall === null) {
				return [];
			}

			$normalizedArgs = $normalizedFuncCall->getArgs();
			if ($functionName === 'strtr' && count($normalizedArgs) !== 2) {
				return [];
			}

			$argsToCheck = [];
			foreach ($checkArgsFunctions[$functionName] as $argIdx) {
				if (!array_key_exists($argIdx, $normalizedArgs)) {
					continue;
				}
				$argsToCheck[$argIdx] = $normalizedArgs[$argIdx];
			}
		}

		$errors = [];
		$functionParameters = $parametersAcceptor->getParameters();
		$typesToCheck = [];
		if (
			in_array($functionName, ['array_intersect_assoc', 'array_diff_assoc'], true)
			&& count($argsToCheck) >= 2
		) {
			$arrayTypes = [];
			$allArgsAreArrays = true;
			foreach ($argsToCheck as $argIdx => $arg) {
				if ($arg->unpack) {
					$allArgsAreArrays = false;
					break;
				}

				$arrayType = $scope->getType($arg->value);
				if (!$arrayType->isArray()->yes()) {
					$allArgsAreArrays = false;
					break;
				}

				$arrayTypes[$argIdx] = $arrayType;
			}

			if ($allArgsAreArrays) {
				$firstArrayType = $arrayTypes[0];
				$typesToCheck[0] = $firstArrayType->intersectKeyArray(
					TypeCombinator::union(...array_slice($arrayTypes, 1)),
				);
				foreach ($arrayTypes as $argIdx => $arrayType) {
					if ($argIdx === 0) {
						continue;
					}

					$typesToCheck[$argIdx] = $arrayType->intersectKeyArray($firstArrayType);
				}
			}
		}

		foreach ($argsToCheck as $argIdx => $arg) {
			$error = $this->parameterCastableToStringCheck->checkParameter(
				$arg,
				$scope,
				$errorMessage,
				static fn (Type $t) => $t->toString(),
				$functionName,
				$this->parameterCastableToStringCheck->getParameterName(
					$arg,
					$argIdx,
					$functionParameters[$argIdx] ?? null,
				),
				$typesToCheck[$argIdx] ?? null,
			);

			if ($error === null) {
				continue;
			}

			$errors[] = $error;
		}

		return $errors;
	}

}
