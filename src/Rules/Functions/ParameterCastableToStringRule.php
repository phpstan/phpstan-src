<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ParameterCastableToStringCheck;
use PHPStan\Rules\Rule;
use PHPStan\Type\Type;
use function array_key_exists;
use function in_array;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ParameterCastableToStringRule implements Rule
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
		$checkFirstArgFunctions = [
			'array_combine',
			'natcasesort',
			'natsort',
			'array_count_values',
			'array_fill_keys',
		];

		if (
			!in_array($functionName, $checkAllArgsFunctions, true)
			&& !in_array($functionName, $checkFirstArgFunctions, true)
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
		$functionParameters = $parametersAcceptor->getParameters();

		if (in_array($functionName, $checkAllArgsFunctions, true)) {
			$argsToCheck = $origArgs;
		} elseif (in_array($functionName, $checkFirstArgFunctions, true)) {
			$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);

			if ($normalizedFuncCall === null) {
				return [];
			}

			$normalizedArgs = $normalizedFuncCall->getArgs();
			if (!array_key_exists(0, $normalizedArgs)) {
				return [];
			}
			$argsToCheck = [0 => $normalizedArgs[0]];
		} else {
			return [];
		}

		$errors = [];

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
			);

			if ($error === null) {
				continue;
			}

			$errors[] = $error;
		}

		return $errors;
	}

}
