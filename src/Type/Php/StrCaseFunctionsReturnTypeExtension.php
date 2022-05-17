<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function in_array;
use function is_callable;

class StrCaseFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'strtoupper',
			'strtolower',
			'mb_strtoupper',
			'mb_strtolower',
			'lcfirst',
			'ucfirst',
			'ucwords',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$argType = $scope->getType($args[0]->value);

		if ($argType instanceof ConstantStringType) {
			$fnName = $functionReflection->getName();
			if (!is_callable($fnName)) {
				throw new ShouldNotHappenException();
			}
			return new ConstantStringType($fnName($argType->getValue()));
		}

		$accessoryTypes = [];
		if ($argType->isNonEmptyString()->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}
		if ($argType->isLiteralString()->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}
		if ($argType->isNumericString()->yes()) {
			$accessoryTypes[] = new AccessoryNumericStringType();
		}
		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

}
