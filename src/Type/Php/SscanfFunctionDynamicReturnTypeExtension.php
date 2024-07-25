<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function preg_match_all;

final class SscanfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sscanf', 'fscanf'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) !== 2) {
			return null;
		}

		$formatType = $scope->getType($args[1]->value);

		if (!$formatType instanceof ConstantStringType) {
			return null;
		}

		if (preg_match_all('/%(\d*)(\[[^\]]+\]|[cdeEfosux]{1})/', $formatType->getValue(), $matches) > 0) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

			for ($i = 0; $i < count($matches[0]); $i++) {
				$length = $matches[1][$i];
				$specifier = $matches[2][$i];

				$type = new StringType();
				if ($length !== '') {
					if (((int) $length) > 1) {
						$type = new IntersectionType([
							$type,
							new AccessoryNonFalsyStringType(),
						]);
					} else {
						$type = new IntersectionType([
							$type,
							new AccessoryNonEmptyStringType(),
						]);
					}
				}

				if (in_array($specifier, ['d', 'o', 'u', 'x'], true)) {
					$type = new IntegerType();
				}

				if (in_array($specifier, ['e', 'E', 'f'], true)) {
					$type = new FloatType();
				}

				$type = TypeCombinator::addNull($type);
				$arrayBuilder->setOffsetValueType(new ConstantIntegerType($i), $type);
			}

			return TypeCombinator::addNull($arrayBuilder->getArray());
		}

		return null;
	}

}
