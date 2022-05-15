<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantArrayType;
use function array_key_exists;
use function array_keys;
use function count;
use function ksort;
use function max;

final class ArgumentsNormalizer
{

	public static function reorderFuncArguments(
		ParametersAcceptor $parametersAcceptor,
		FuncCall $functionCall,
	): ?FuncCall
	{
		$reorderedArgs = self::reorderArgs($parametersAcceptor, $functionCall);

		if ($reorderedArgs === null) {
			return null;
		}

		return new FuncCall(
			$functionCall->name,
			$reorderedArgs,
			$functionCall->getAttributes(),
		);
	}

	public static function reorderMethodArguments(
		ParametersAcceptor $parametersAcceptor,
		MethodCall $methodCall,
	): ?MethodCall
	{
		$reorderedArgs = self::reorderArgs($parametersAcceptor, $methodCall);

		if ($reorderedArgs === null) {
			return null;
		}

		return new MethodCall(
			$methodCall->var,
			$methodCall->name,
			$reorderedArgs,
			$methodCall->getAttributes(),
		);
	}

	public static function reorderStaticCallArguments(
		ParametersAcceptor $parametersAcceptor,
		StaticCall $staticCall,
	): ?StaticCall
	{
		$reorderedArgs = self::reorderArgs($parametersAcceptor, $staticCall);

		if ($reorderedArgs === null) {
			return null;
		}

		return new StaticCall(
			$staticCall->class,
			$staticCall->name,
			$reorderedArgs,
			$staticCall->getAttributes(),
		);
	}

	/**
	 * @return ?array<int, Arg>
	 */
	private static function reorderArgs(ParametersAcceptor $parametersAcceptor, CallLike $callLike): ?array
	{
		$signatureParameters = $parametersAcceptor->getParameters();
		$callArgs = $callLike->getArgs();

		if (count($callArgs) === 0) {
			return [];
		}

		$hasNamedArgs = false;
		foreach ($callArgs as $arg) {
			if ($arg->name !== null) {
				$hasNamedArgs = true;
				break;
			}
		}
		if (!$hasNamedArgs) {
			return $callArgs;
		}

		$argumentPositions = [];
		foreach ($signatureParameters as $i => $parameter) {
			$argumentPositions[$parameter->getName()] = $i;
		}

		$reorderedArgs = [];
		foreach ($callArgs as $i => $arg) {
			if ($arg->name === null) {
				// add regular args as is
				$reorderedArgs[$i] = $arg;
			} elseif (array_key_exists($arg->name->toString(), $argumentPositions)) {
				$argName = $arg->name->toString();
				$arg = clone $arg;

				// turn named arg into regular numeric arg
				$arg->name = null;
				// order named args into the position the signature expects them
				$reorderedArgs[$argumentPositions[$argName]] = $arg;
			}
		}

		if (count($reorderedArgs) === 0) {
			return [];
		}

		// fill up all wholes with default values until the last given argument
		for ($j = 0; $j < max(array_keys($reorderedArgs)); $j++) {
			if (array_key_exists($j, $reorderedArgs)) {
				continue;
			}
			if (!array_key_exists($j, $signatureParameters)) {
				throw new ShouldNotHappenException('Parameter signatures cannot have wholes');
			}

			$parameter = $signatureParameters[$j];

			// we can only fill up optional parameters with default values
			if (!$parameter->isOptional()) {
				return null;
			}

			$defaultValue = $parameter->getDefaultValue();
			if ($defaultValue === null) {
				if (!$parameter->isVariadic()) {
					throw new ShouldNotHappenException('A optional parameter must have a default value');
				}
				$defaultValue = new ConstantArrayType([], []);
			}

			$reorderedArgs[$j] = new Arg(
				new TypeExpr($defaultValue),
			);
		}

		ksort($reorderedArgs);

		return $reorderedArgs;
	}

}
