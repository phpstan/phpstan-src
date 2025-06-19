<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function ltrim;

#[AutowiredService]
final class LtrimFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ltrim';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$string = $scope->getType($functionCall->getArgs()[0]->value);

		$accessory = [];
		$defaultType = new StringType();
		if ($string->isLowercaseString()->yes()) {
			$accessory[] = new AccessoryLowercaseStringType();
		}
		if ($string->isUppercaseString()->yes()) {
			$accessory[] = new AccessoryUppercaseStringType();
		}
		if (count($accessory) > 0) {
			$accessory[] = new StringType();
			$defaultType = new IntersectionType($accessory);
		}

		if (count($functionCall->getArgs()) !== 2) {
			return $defaultType;
		}

		$trimChars = $scope->getType($functionCall->getArgs()[1]->value);

		if ($trimChars instanceof ConstantStringType && $trimChars->getValue() === '\\' && $string->isClassString()->yes()) {
			if ($string instanceof ConstantStringType) {
				return new ConstantStringType(ltrim($string->getValue(), $trimChars->getValue()), true);
			}

			return new ClassStringType();
		}

		return $defaultType;
	}

}
