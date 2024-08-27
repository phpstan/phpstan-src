<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Nette\Utils\Strings;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
use function str_repeat;
use function strlen;

final class StrRepeatFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'str_repeat';
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

		$multiplierType = $scope->getType($args[1]->value);

		if ((new ConstantIntegerType(0))->isSuperTypeOf($multiplierType)->yes()) {
			return new ConstantStringType('');
		}

		if (IntegerRangeType::fromInterval(null, 0)->isSuperTypeOf($multiplierType)->yes()) {
			return new NeverType();
		}

		$inputType = $scope->getType($args[0]->value);
		if (
			$inputType instanceof ConstantStringType
			&& $multiplierType instanceof ConstantIntegerType
			// don't generate type too big to avoid hitting memory limit
			&& strlen($inputType->getValue()) * $multiplierType->getValue() < 100
		) {
			return new ConstantStringType(str_repeat($inputType->getValue(), $multiplierType->getValue()));
		}

		$accessoryTypes = [];
		if ($inputType->isNonEmptyString()->yes()) {
			if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($multiplierType)->yes()) {
				if ($inputType->isNonFalsyString()->yes()) {
					$accessoryTypes[] = new AccessoryNonFalsyStringType();
				} else {
					$accessoryTypes[] = new AccessoryNonEmptyStringType();
				}
			}
		}

		if ($inputType->isLiteralString()->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();

			if (
				$inputType->isNumericString()->yes()
				&& IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($multiplierType)->yes()
			) {
				$onlyNumbers = true;
				foreach ($inputType->getConstantStrings() as $constantString) {
					if (Strings::match($constantString->getValue(), '#^[0-9]+$#') === null) {
						$onlyNumbers = false;
						break;
					}
				}

				if ($onlyNumbers) {
					$accessoryTypes[] = new AccessoryNumericStringType();
				}
			}
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}
		return new StringType();
	}

}
