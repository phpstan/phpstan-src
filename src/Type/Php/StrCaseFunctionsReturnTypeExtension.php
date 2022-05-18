<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
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
		$fnName = $functionReflection->getName();
		if (!is_callable($fnName)) {
			throw new ShouldNotHappenException();
		}

		if (count($args) === 1) {
			$constantStrings = TypeUtils::getConstantStrings($argType);
			if (count($constantStrings) > 0) {
				$strings = [];

				foreach ($constantStrings as $constantString) {
					$strings[] = new ConstantStringType($fnName($constantString->getValue()));
				}

				return TypeCombinator::union(...$strings);
			}
		}

		if ($argType->isNumericString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNumericStringType(),
			]);
		}

		if ($argType->isNonEmptyString()->yes()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNonEmptyStringType(),
			]);
		}

		return new StringType();
	}

}
