<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function is_bool;
use function mb_substr;
use function substr;

class SubstrDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['substr', 'mb_substr'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		if (count($args) >= 2) {
			$string = $scope->getType($args[0]->value);
			$offset = $scope->getType($args[1]->value);

			$negativeOffset = IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($offset)->yes();
			$zeroOffset = (new ConstantIntegerType(0))->isSuperTypeOf($offset)->yes();
			$length = null;
			$positiveLength = false;
			$maybeOneLength = false;

			if (count($args) === 3) {
				$length = $scope->getType($args[2]->value);
				$positiveLength = IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($length)->yes();
				$maybeOneLength = !(new ConstantIntegerType(1))->isSuperTypeOf($length)->no();
			}

			$constantStrings = $string->getConstantStrings();
			if (
				count($constantStrings) > 0
				&& $offset instanceof ConstantIntegerType
				&& ($length === null || $length instanceof ConstantIntegerType)
			) {
				$results = [];
				foreach ($constantStrings as $constantString) {
					if ($length !== null) {
						if ($functionReflection->getName() === 'mb_substr') {
							$substr = mb_substr($constantString->getValue(), $offset->getValue(), $length->getValue());
						} else {
							$substr = substr($constantString->getValue(), $offset->getValue(), $length->getValue());
						}
					} else {
						if ($functionReflection->getName() === 'mb_substr') {
							$substr = mb_substr($constantString->getValue(), $offset->getValue());
						} else {
							$substr = substr($constantString->getValue(), $offset->getValue());
						}
					}

					if (is_bool($substr)) {
						$results[] = new ConstantBooleanType($substr);
					} else {
						$results[] = new ConstantStringType($substr);
					}
				}

				return TypeCombinator::union(...$results);
			}

			if ($string->isNonEmptyString()->yes() && ($negativeOffset || $zeroOffset && $positiveLength)) {
				if ($string->isNonFalsyString()->yes() && !$maybeOneLength) {
					return new IntersectionType([
						new StringType(),
						new AccessoryNonFalsyStringType(),
					]);

				}
				return new IntersectionType([
					new StringType(),
					new AccessoryNonEmptyStringType(),
				]);
			}
		}

		return null;
	}

}
