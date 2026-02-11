<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;

#[AutowiredService]
final class ArrayCountValuesDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_count_values';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();

		if (!isset($args[0])) {
			return null;
		}

		$inputType = $scope->getType($args[0]->value);

		$arrayTypes = $inputType->getArrays();
		$isInputNonEmpty = $inputType->isIterableAtLeastOnce()->yes();

		$outputTypes = [];
		$allowedValues = new UnionType([new IntegerType(), new StringType()]);

		foreach ($arrayTypes as $arrayType) {
			$itemType = TypeCombinator::intersect($arrayType->getItemType(), $allowedValues);
			if ($itemType->isNever()->yes()) {
				continue;
			}

			$resultArrayType = new ArrayType($itemType->toArrayKey(), IntegerRangeType::fromInterval(1, null));

			if ($isInputNonEmpty) {
				$outputTypes[] = new IntersectionType([
					$resultArrayType,
					new NonEmptyArrayType(),
				]);
			} else {
				$outputTypes[] = $resultArrayType;
			}
		}

		if (count($outputTypes) === 0) {
			return new ConstantArrayType([], []);
		}

		return TypeCombinator::union(...$outputTypes);
	}

}
