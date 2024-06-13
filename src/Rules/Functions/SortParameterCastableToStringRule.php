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
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use function array_key_exists;
use function in_array;
use const SORT_FLAG_CASE;
use const SORT_LOCALE_STRING;
use const SORT_NATURAL;
use const SORT_NUMERIC;
use const SORT_REGULAR;
use const SORT_STRING;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class SortParameterCastableToStringRule implements Rule
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

		if (!in_array($functionName, ['array_unique', 'sort', 'rsort', 'asort', 'arsort'], true)) {
			return [];
		}

		$origArgs = $node->getArgs();
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$origArgs,
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$functionParameters = $parametersAcceptor->getParameters();

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);

		if ($normalizedFuncCall === null) {
			return [];
		}

		$normalizedArgs = $normalizedFuncCall->getArgs();
		if (!array_key_exists(0, $normalizedArgs)) {
			return [];
		}

		$argsToCheck = [0 => $normalizedArgs[0]];
		$flags = null;
		if (array_key_exists(1, $normalizedArgs)) {
			$flags = $scope->getType($normalizedArgs[1]->value);
		} elseif (array_key_exists(1, $functionParameters)) {
			$flags = $functionParameters[1]->getDefaultValue();
		}

		if ($flags === null || $flags->equals(new ConstantIntegerType(SORT_REGULAR))) {
			return [];
		}

		$constantIntFlags = TypeUtils::getConstantIntegers($flags);
		$mustBeCastableToString = $mustBeCastableToFloat = $constantIntFlags === [];

		foreach ($constantIntFlags as $flag) {
			if ($flag->getValue() === SORT_NUMERIC) {
				$mustBeCastableToFloat = true;
			} elseif (in_array($flag->getValue() & (~SORT_FLAG_CASE), [SORT_STRING, SORT_LOCALE_STRING, SORT_NATURAL], true)) {
				$mustBeCastableToString = true;
			}
		}

		if ($mustBeCastableToString && !$mustBeCastableToFloat) {
			$errorMessage = 'Parameter %s of function %s expects an array of values castable to string, %s given.';
			$castFn = static fn (Type $t) => $t->toString();
		} elseif ($mustBeCastableToString) {
			$errorMessage = 'Parameter %s of function %s expects an array of values castable to string and float, %s given.';
			$castFn = static function (Type $t): Type {
				$float = $t->toFloat();

				return $float instanceof ErrorType
					? $float
					: $t->toString();
			};
		} elseif ($mustBeCastableToFloat) {
			$errorMessage = 'Parameter %s of function %s expects an array of values castable to float, %s given.';
			$castFn = static fn (Type $t) => $t->toFloat();
		} else {
			return [];
		}

		$errors = [];

		foreach ($argsToCheck as $argIdx => $arg) {
			$error = $this->parameterCastableToStringCheck->checkParameter(
				$arg,
				$scope,
				$errorMessage,
				$castFn,
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
