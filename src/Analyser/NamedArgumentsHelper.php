<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Reflection\ParametersAcceptor;

final class NamedArgumentsHelper {
	public static function reorderFuncArguments(
		ParametersAcceptor $parametersAcceptor,
		FuncCall $functionCall
	): FuncCall
	{
		return new FuncCall(
			$functionCall->name,
			self::reorderArgs($parametersAcceptor, $functionCall),
			$functionCall->getAttributes()
		);
	}

	public static function reorderMethodArguments(
		ParametersAcceptor $parametersAcceptor,
		MethodCall $methodCall
	): MethodCall
	{
		return new MethodCall(
			$methodCall->var,
			$methodCall->name,
			self::reorderArgs($parametersAcceptor, $methodCall),
			$methodCall->getAttributes()
		);
	}

	private static function reorderArgs(ParametersAcceptor $parametersAcceptor, CallLike $callLike) {
		$signatureParameters = $parametersAcceptor->getParameters();
		$callArgs = $callLike->getArgs();

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
				// order named args into the position the signature expects them
				$reorderedArgs[$argumentPositions[$arg->name->toString()]] = $arg;
			}
		}

		return $reorderedArgs;
	}

}
