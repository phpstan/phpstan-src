<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strrev;

#[AutowiredService]
final class StrrevFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'strrev';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$inputType = $scope->getType($args[0]->value);
		$constantStrings = $inputType->getConstantStrings();
		if (count($constantStrings) > 0) {
			$resultTypes = [];
			foreach ($constantStrings as $constantString) {
				$resultTypes[] = new ConstantStringType(strrev($constantString->getValue()));
			}

			return TypeCombinator::union(...$resultTypes);
		}

		$accessoryTypes = [];
		if ($inputType->isNonFalsyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($inputType->isNonEmptyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}
		if ($inputType->isLowercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}
		if ($inputType->isUppercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();

			return new IntersectionType($accessoryTypes);
		}

		return null;
	}

}
