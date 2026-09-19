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
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class StrShuffleFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_shuffle';
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

		// The result contains every byte of the input exactly once,
		// so it keeps its emptiness and its casing.
		$inputType = $scope->getType($args[0]->value);
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
