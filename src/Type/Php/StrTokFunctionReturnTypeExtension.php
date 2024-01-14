<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;

class StrTokFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'strtok';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) !== 2) {
			return null;
		}

		$delimiterType = $scope->getType($functionCall->getArgs()[0]->value);
		$isEmptyString = (new ConstantStringType(''))->isSuperTypeOf($delimiterType);
		if ($isEmptyString->yes()) {
			return new ConstantBooleanType(false);
		}

		if ($isEmptyString->no()) {
			return new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]);
		}

		return null;
	}

}
