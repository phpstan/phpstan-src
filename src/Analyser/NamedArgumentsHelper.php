<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Reflection\ParametersAcceptor;

final class NamedArgumentsHelper {
	public static function reorderArguments(
		ParametersAcceptor $parametersAcceptor,
		FuncCall $functionCall
	): FuncCall
	{
		$signatureParameters = $parametersAcceptor->getParameters();
		$callArgs = $functionCall->getArgs();

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

		return new FuncCall($functionCall->name, $reorderedArgs, $functionCall->getAttributes());
	}

}
