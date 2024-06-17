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
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class ImplodeParameterCastableToStringRule implements Rule
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
		if (!in_array($functionName, ['implode', 'join'], true)) {
			return [];
		}

		$origArgs = $node->getArgs();
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$origArgs,
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);

		if ($normalizedFuncCall === null) {
			return [];
		}

		$normalizedArgs = $normalizedFuncCall->getArgs();
		$errorMessage = 'Parameter %s of function %s expects array<string>, %s given.';
		if (count($normalizedArgs) === 1) {
			$argsToCheck = [0 => $normalizedArgs[0]];
		} elseif (count($normalizedArgs) === 2) {
			$argsToCheck = [1 => $normalizedArgs[1]];
		} else {
			return [];
		}

		$origNamedArgs = [];
		foreach ($origArgs as $arg) {
			if ($arg->unpack || $arg->name === null) {
				continue;
			}

			$origNamedArgs[$arg->name->toString()] = $arg;
		}

		$errors = [];

		foreach ($argsToCheck as $argIdx => $arg) {
			// implode has weird variants, so $array has to be fixed. It's especially weird with named arguments.
			if (array_key_exists('array', $origNamedArgs)) {
				$argName = '$array';
			} elseif (array_key_exists('separator', $origNamedArgs) && count($origArgs) === 1) {
				$argName = '$separator';
			} else {
				$argName = sprintf('#%d $array', $argIdx + 1);
			}

			$error = $this->parameterCastableToStringCheck->checkParameter(
				$arg,
				$scope,
				$errorMessage,
				static fn (Type $t) => $t->toString(),
				$functionName,
				$argName,
			);

			if ($error === null) {
				continue;
			}

			$errors[] = $error;
		}

		return $errors;
	}

}
