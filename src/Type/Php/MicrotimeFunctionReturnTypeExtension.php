<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

class MicrotimeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'microtime';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return new StringType();
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		$isTrueType = $argType->isTrue();
		$isFalseType = $argType->isFalse();
		$compareTypes = $isTrueType->compareTo($isFalseType);
		if ($compareTypes === $isTrueType) {
			return new FloatType();
		}
		if ($compareTypes === $isFalseType) {
			return new StringType();
		}

		if ($argType instanceof MixedType) {
			return new BenevolentUnionType([new StringType(), new FloatType()]);
		}

		return new UnionType([new StringType(), new FloatType()]);
	}

}
