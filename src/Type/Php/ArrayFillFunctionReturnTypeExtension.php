<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class ArrayFillFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const MAX_SIZE_USE_CONSTANT_ARRAY = 100;

	private PhpVersion $phpVersion;

	public function __construct(PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_fill';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 3) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$startIndexType = $scope->getType($functionCall->getArgs()[0]->value);
		$numberType = $scope->getType($functionCall->getArgs()[1]->value);
		$valueType = $scope->getType($functionCall->getArgs()[2]->value);

		if ($numberType instanceof IntegerRangeType) {
			if ($numberType->getMin() < 0) {
				return TypeCombinator::union(
					new ArrayType(new IntegerType(), $valueType),
					new ConstantBooleanType(false)
				);
			}
		}

		// check against negative-int, which is not allowed
		if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($numberType)->yes()) {
			if ($this->phpVersion->throwsValueErrorForInternalFunctions()) {
				return new NeverType();
			}
			return new ConstantBooleanType(false);
		}

		if (
			$startIndexType instanceof ConstantIntegerType
			&& $numberType instanceof ConstantIntegerType
			&& $numberType->getValue() <= self::MAX_SIZE_USE_CONSTANT_ARRAY
		) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			$nextIndex = $startIndexType->getValue();
			for ($i = 0; $i < $numberType->getValue(); $i++) {
				$arrayBuilder->setOffsetValueType(
					new ConstantIntegerType($nextIndex),
					$valueType
				);
				if ($nextIndex < 0) {
					$nextIndex = 0;
				} else {
					$nextIndex++;
				}
			}

			return $arrayBuilder->getArray();
		}

		if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($numberType)->yes()) {
			return new IntersectionType([
				new ArrayType(new IntegerType(), $valueType),
				new NonEmptyArrayType(),
			]);
		}

		return new ArrayType(new IntegerType(), $valueType);
	}

}
