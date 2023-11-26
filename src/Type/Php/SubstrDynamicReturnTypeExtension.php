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
use function is_bool;
use function substr;

class SubstrDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'substr';
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

			if (count($args) === 3) {
				$length = $scope->getType($args[2]->value);
				$positiveLength = IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($length)->yes();
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
						$substr = substr(
							$constantString->getValue(),
							$offset->getValue(),
							$length->getValue(),
						);
					} else {
						$substr = substr(
							$constantString->getValue(),
							$offset->getValue(),
						);
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
				if ($string->isNonFalsyString()->yes()) {
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
