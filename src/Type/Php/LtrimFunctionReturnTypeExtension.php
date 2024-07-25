<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use function ltrim;

final class LtrimFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ltrim';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) !== 2) {
			return null;
		}

		$string = $scope->getType($functionCall->getArgs()[0]->value);
		$trimChars = $scope->getType($functionCall->getArgs()[1]->value);

		if ($trimChars instanceof ConstantStringType && $trimChars->getValue() === '\\' && $string->isClassStringType()->yes()) {
			if ($string instanceof ConstantStringType) {
				return new ConstantStringType(ltrim($string->getValue(), $trimChars->getValue()), true);
			}

			return new ClassStringType();
		}

		return null;
	}

}
