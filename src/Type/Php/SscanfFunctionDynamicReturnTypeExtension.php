<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function preg_match_all;

class SscanfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'sscanf';
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

		if (preg_match_all('/%[cdeEfosux]{1}/', $formatType->getValue(), $matches) > 0) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();

			for ($i = 0; $i < count($matches[0]); $i++) {
				$type = new StringType();

				if (in_array($matches[0][$i], ['%d', '%o', '%u', '%x'], true)) {
					$type = new IntegerType();
				}

				if (in_array($matches[0][$i], ['%e', '%E', '%f'], true)) {
					$type = new FloatType();
				}

				$arrayBuilder->setOffsetValueType(new ConstantIntegerType($i), $type);
			}

			return TypeCombinator::addNull($arrayBuilder->getArray());
		}

		return null;
	}

}
