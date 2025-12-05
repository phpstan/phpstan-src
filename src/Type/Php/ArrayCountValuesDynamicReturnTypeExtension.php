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
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
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

		$outputTypes = [];

		foreach ($arrayTypes as $arrayType) {
			$itemType = $arrayType->getItemType();

			if ($itemType instanceof UnionType) {
				$itemType = $itemType->filterTypes(
					static fn ($type) => !$type->toArrayKey() instanceof ErrorType,
				);
			}

			if ($itemType->toArrayKey() instanceof ErrorType) {
				continue;
			}

			$outputTypes[] = TypeCombinator::intersect(
				new ArrayType($itemType, IntegerRangeType::fromInterval(1, null)),
				new NonEmptyArrayType(),
			);
		}

		if (count($outputTypes) === 0) {
			return new ConstantArrayType([], []);
		}

		return TypeCombinator::union(...$outputTypes);
	}

}
