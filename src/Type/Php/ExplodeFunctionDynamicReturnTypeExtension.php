<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;
use const PHP_INT_MAX;

final class ExplodeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const CONST_ARRAY_LIMIT = 8;

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'explode';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$delimiterType = $scope->getType($args[0]->value);
		$isEmptyString = (new ConstantStringType(''))->isSuperTypeOf($delimiterType);
		if ($isEmptyString->yes()) {
			if ($this->phpVersion->throwsTypeErrorForInternalFunctions()) {
				return new NeverType();
			}
			return new ConstantBooleanType(false);
		}

		$stringType = $scope->getType($args[1]->value);
		$accessory = [];
		if ($stringType->isLowercaseString()->yes()) {
			$accessory[] = new AccessoryLowercaseStringType();
		}
		if ($stringType->isUppercaseString()->yes()) {
			$accessory[] = new AccessoryUppercaseStringType();
		}
		if (count($accessory) > 0) {
			$accessory[] = new StringType();
			$returnValueType = new IntersectionType($accessory);
		} else {
			$returnValueType = new StringType();
		}

		$returnType = TypeCombinator::intersect(new ArrayType(new IntegerType(), $returnValueType), new AccessoryArrayListType());
		$limitType = isset($args[2]) ? $scope->getType($args[2]->value) : new ConstantIntegerType(PHP_INT_MAX);
		if (IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($limitType)->yes()) {
			$constantScalarTypes = $limitType->getConstantScalarTypes();
			if (count($constantScalarTypes) === 1 && IntegerRangeType::fromInterval(0, self::CONST_ARRAY_LIMIT)->isSuperTypeOf($limitType)->yes()) {
				$limit = (int) $constantScalarTypes[0]->getValue() ?: 1; // 0 is treated as 1

				$builder = ConstantArrayTypeBuilder::createEmpty();
				for ($i = 0; $i < $limit; $i++) {
					$builder->setOffsetValueType(null, $returnValueType, $i !== 0);
				}

				$returnType = $builder->getArray();
			} else {
				$returnType = TypeCombinator::intersect($returnType, new NonEmptyArrayType());
			}
		}

		if (!$this->phpVersion->throwsValueErrorForInternalFunctions() && $isEmptyString->maybe()) {
			$returnType = TypeCombinator::union($returnType, new ConstantBooleanType(false));
		}

		if ($delimiterType instanceof MixedType) {
			$returnType = TypeUtils::toBenevolentUnion($returnType);
		}

		return $returnType;
	}

}
