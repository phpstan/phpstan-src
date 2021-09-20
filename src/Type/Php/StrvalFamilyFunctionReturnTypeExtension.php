<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;

class StrvalFamilyFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
		Scope $scope
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
				return $argType->toInteger();
			case 'boolval':
				return $argType->toBoolean();
			case 'floatval':
			case 'doubleval':
				return $argType->toFloat();
			default:
				throw new \PHPStan\ShouldNotHappenException();
		}
	}

}
