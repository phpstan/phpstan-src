<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class StrPadFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_pad';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return new StringType();
		}

		$inputType = $scope->getType($args[0]->value);
		$lengthType = $scope->getType($args[1]->value);

		$accessoryTypes = [];
		if ($inputType->isNonFalsyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($inputType->isNonEmptyString()->yes() || IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($lengthType)->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if (count($args) < 3) {
			$padStringType = null;
		} else {
			$padStringType = $scope->getType($args[2]->value);
		}

		if ($inputType->isLiteralString()->yes() && ($padStringType === null || $padStringType->isLiteralString()->yes())) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}
		if ($inputType->isLowercaseString()->yes() && ($padStringType === null || $padStringType->isLowercaseString()->yes())) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}
		if ($inputType->isUppercaseString()->yes() && ($padStringType === null || $padStringType->isUppercaseString()->yes())) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

}
