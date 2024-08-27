<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function count;
use function in_array;

final class StrvalFamilyFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const FUNCTIONS = [
		'strval',
		'intval',
		'boolval',
		'floatval',
		'doubleval',
	];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), self::FUNCTIONS, true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		if (count($functionCall->getArgs()) === 0) {
			return new NullType();
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);

		switch ($functionReflection->getName()) {
			case 'strval':
				return $argType->toString();
			case 'intval':
				$type = $argType->toInteger();
				return $type instanceof ErrorType ? new IntegerType() : $type;
			case 'boolval':
				return $argType->toBoolean();
			case 'floatval':
			case 'doubleval':
				$type = $argType->toFloat();
				return $type instanceof ErrorType ? new FloatType() : $type;
			default:
				throw new ShouldNotHappenException();
		}
	}

}
