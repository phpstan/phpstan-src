<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function count;

#[AutowiredService]
final class CountCharsFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'count_chars';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();

		if (count($args) < 1) {
			return null;
		}

		$modeType = count($args) === 2 ? $scope->getType($args[1]->value) : new ConstantIntegerType(0);
		$throwsValueError = $scope->getPhpVersion()->throwsValueErrorForInternalFunctions();

		if (IntegerRangeType::fromInterval(0, 2)->isSuperTypeOf($modeType)->yes()) {
			$arrayType = new ArrayType(new IntegerType(), new IntegerType());

			return $throwsValueError->yes()
				? $arrayType
				: TypeUtils::toBenevolentUnion(new UnionType([$arrayType, new ConstantBooleanType(false)]));
		}

		$stringType = new StringType();

		return $throwsValueError->yes()
			? $stringType
			: TypeUtils::toBenevolentUnion(new UnionType([$stringType, new ConstantBooleanType(false)]));
	}

}
