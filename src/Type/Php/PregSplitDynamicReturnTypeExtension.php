<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function strtolower;

class PregSplitDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private BitwiseFlagHelper $bitwiseFlagAnalyser,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'preg_split';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$flagsArg = $functionCall->getArgs()[3] ?? null;

		if ($flagsArg !== null && $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($flagsArg->value, $scope, 'PREG_SPLIT_OFFSET_CAPTURE')->yes()) {
			$type = new ArrayType(
				new IntegerType(),
				new ConstantArrayType([new ConstantIntegerType(0), new ConstantIntegerType(1)], [new StringType(), IntegerRangeType::fromInterval(0, null)]),
			);
			return TypeCombinator::union($type, new ConstantBooleanType(false));
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}

}
