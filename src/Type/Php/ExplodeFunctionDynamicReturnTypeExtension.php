<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

class ExplodeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

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
		if (count($functionCall->getArgs()) < 2) {
			return null;
		}

		$delimiterType = $scope->getType($functionCall->getArgs()[0]->value);
		$isSuperset = (new ConstantStringType(''))->isSuperTypeOf($delimiterType);
		if ($isSuperset->yes()) {
			if ($this->phpVersion->getVersionId() >= 80000) {
				return new NeverType();
			}
			return new ConstantBooleanType(false);
		} elseif ($isSuperset->no()) {
			$arrayType = AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new StringType()));
			if (
				!isset($functionCall->getArgs()[2])
				|| IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($scope->getType($functionCall->getArgs()[2]->value))->yes()
			) {
				return TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
			}

			return $arrayType;
		}

		$returnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		if ($delimiterType instanceof MixedType) {
			return TypeUtils::toBenevolentUnion($returnType);
		}

		return $returnType;
	}

}
