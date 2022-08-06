<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use function ltrim;

class LtrimFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

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

		if ($trimChars instanceof ConstantStringType && $trimChars->getValue() === '\\') {
			if ($string instanceof ConstantStringType && $string->isClassString()) {
				return new ConstantStringType(ltrim($string->getValue(), $trimChars->getValue()), true);
			}

			if ($string instanceof ClassStringType) {
				return new ClassStringType();
			}
		}

		return null;
	}

}
