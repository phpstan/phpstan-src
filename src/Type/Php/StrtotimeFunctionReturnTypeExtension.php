<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use function array_map;
use function array_unique;
use function count;
use function gettype;
use function min;
use function strtotime;

final class StrtotimeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'strtotime';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();
		if (count($functionCall->getArgs()) === 0) {
			return $defaultReturnType;
		}
		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($argType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($defaultReturnType);
		}
		$results = array_unique(array_map(static fn (ConstantStringType $string): int|bool => strtotime($string->getValue()), $argType->getConstantStrings()));
		$resultTypes = array_unique(array_map(static fn (int|bool $value): string => gettype($value), $results));

		if (count($resultTypes) !== 1 || count($results) === 0) {
			return $defaultReturnType;
		}

		if ($results[0] === false) {
			return new ConstantBooleanType(false);
		}

		// 2nd param $baseTimestamp is too non-deterministic so simply return int
		if (count($functionCall->getArgs()) > 1) {
			return new IntegerType();
		}

		// if it is positive we can narrow down to positive-int as long as time flows forward
		if (min(array_map('intval', $results)) > 0) {
			return IntegerRangeType::createAllGreaterThan(0);
		}

		return new IntegerType();
	}

}
