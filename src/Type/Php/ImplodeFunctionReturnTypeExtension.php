<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function implode;
use function in_array;

class ImplodeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'implode',
			'join',
		], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 1) {
			$argType = $scope->getType($args[0]->value);
			if ($argType->isArray()->yes()) {
				return $this->implode($argType, new ConstantStringType(''));
			}
		}

		if (count($args) !== 2) {
			return new StringType();
		}

		$separatorType = $scope->getType($args[0]->value);
		$arrayType = $scope->getType($args[1]->value);

		return $this->implode($arrayType, $separatorType);
	}

	private function implode(Type $arrayType, Type $separatorType): Type
	{
		if ($arrayType instanceof ConstantArrayType && $separatorType instanceof ConstantStringType) {
			$constantType = $this->inferConstantType($arrayType, $separatorType);
			if ($constantType !== null) {
				return $constantType;
			}
		}

		$accessoryTypes = [];
		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			if ($arrayType->getIterableValueType()->isNonEmptyString()->yes() || $separatorType->isNonEmptyString()->yes()) {
				$accessoryTypes[] = new AccessoryNonEmptyStringType();
			}
		}

		// implode is one of the four functions that can produce literal strings as blessed by the original RFC: wiki.php.net/rfc/is_literal
		if ($arrayType->getIterableValueType()->isLiteralString()->yes() && $separatorType->isLiteralString()->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

	private function inferConstantType(ConstantArrayType $arrayType, ConstantStringType $separatorType): ?Type
	{
		$strings = [];
		foreach ($arrayType->getAllArrays() as $array) {
			$valueTypes = $array->getValueTypes();

			$arrayValues = [];
			foreach ($valueTypes as $valueType) {
				if (!$valueType instanceof ConstantScalarType) {
					return null;
				}
				$arrayValues[] = $valueType->getValue();
			}

			$strings[] = new ConstantStringType(implode($separatorType->getValue(), $arrayValues));
		}

		return TypeCombinator::union(...$strings);
	}

}
